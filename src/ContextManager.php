<?php
namespace sqonk\phext\context;

/**
*
* Context Management
* 
* @package		phext
* @subpackage	context
* @version		1
* 
* @license		MIT see license.txt
* @copyright	2019 Sqonk Pty Ltd.
*
*
* This file is distributed
* on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
* express or implied. See the License for the specific language governing
* permissions and limitations under the License.
*/

/*
    Contexts create a block level scope on a resource and automatically manage the creation
    and cleanup of that resource irrespective of any exceptions that arise while in use, the
    primary intention being to help remove the possibility of resource leaks. On top of that they
    aid in keeping your code lean by automating the standard logic involved with operating such
    resources.

    They are a phsuedo implementation of the context managers in Python. While PHP 7 and
    earlier do not currently allow for a 1:1 elegant solution, the below intepretation makes
    use of function callbacks to achieve something similar.

    While alternative implementations make clever use of generators and 'yield' to push the 
    resources to a 1-cycle foreach loop, the readability is somewhat lost when you follow the code.
    
	This implementation attempts to keep the code readable and be self-explanatory as to what is happening 
	when being studied by another person, even if it is a little more verbose.

    In many of the methods a special dedicated error handler is temporarily injected to convert
    PHP errors into raised Exceptions. Keep in mind that while inside a context manager any standard
    error handling systems your code is using will be overriden until the manager exits.
*/

use sqonk\phext\core\arrays;

function _with_exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
}

abstract class ContextManager
{
    public abstract function do(callable $callback);
	
	// Alis of ContextManager::do().
	public function while(callable $callback) {
		$this->do($callback);
	}
    
    final protected function installErrorHandler()
    {
        set_error_handler("\sqonk\phext\context\_with_exception_error_handler");
    }
}

class SupressErrors extends ContextManager
{
    public function do(callable $do)
    {
        $this->installErrorHandler();
        try {
            $do();
        } 
		catch (\Throwable $error) {
			// do nothing.
		}
        finally {
            restore_error_handler();
        }
    }
}


class FileHandle extends ContextManager
{
    protected $path;
    protected $mode;
    
    public function __construct(string $filePath, string $mode = 'r')
    {
        $this->path = $filePath;
        $this->mode = $mode;
    }
    
    public function do(callable $do)
    {
        $this->installErrorHandler();
        $fh = null;
        try {
            if (! $fh = fopen($this->path, $this->mode))
                throw new \RuntimeException("[{$this->path}] could not be opened, empty handle returned.");
			$lockType = arrays::contains(['r', 'r+'], $this->mode) ? LOCK_SH : LOCK_EX;
			@flock($fh, $lockType);
            $do($fh);
        }
        finally {
            if ($fh) {
            	@flock($fh, LOCK_UN);
				@fclose($fh);
            }
            restore_error_handler();
        }
    }
}

class StreamHandle extends FileHandle
{
    protected $chunkM;
    
    public function __construct(string $filePath, int $chunkMultiplier = 1024)
    {
        parent::__construct($filePath, 'r');
        $this->chunkM = $chunkMultiplier;
    }
    
    public function do(callable $do)
    {
        parent::do(function($fh) use($do) {
            $chunk = $this->chunkM * $this->chunkM;
            while (! feof($fh)) 
            {
                $buffer = fread($fh, $chunk);
                $do($buffer);
            }
        });
    }
}

class TmpFileHandle extends ContextManager
{
    public function do(callable $do)
    {
        $this->installErrorHandler();
        $fh = null;
        try {
            if (! $fh = tmpfile())
                throw new \RuntimeException('A temporary file could not be created.');
			
            $do($fh);
        }
        finally {
            if ($fh) 
				@fclose($fh);
            
            restore_error_handler();
        }
    }
}

class Image extends ContextManager
{
    protected $path;
    
    public function __construct(string $filePath)
    {
        $this->path = $filePath;
    }
    
    public function do(callable $do)
    {
        if (! function_exists('imagecreatefromstring'))
            throw new \RuntimeException('The image context requires that your PHP runtime has the GD extension loaded.');
        
        $this->installErrorHandler();
        $img = null;
        try 
        {
            $ext = pathinfo($this->path, PATHINFO_EXTENSION);
            switch ($ext)
            {
                case 'jpeg':
                case 'jpg':
                    $img = imagecreatefromjpeg($this->path);
                    break;
                case 'png':
                    $img = imagecreatefrompng($this->path);
                    break;
                case 'gif':
                    $img = imagecreatefromgif($this->path);
                    break;
                case 'bmp':
                    $img = imagecreatefrombmp($this->path);
                    break;
                default:
                    $img = imagecreatefromstring(file_get_contents($this->path));
                    break;
            }
            if (! $img)
                throw new \RuntimeException("[{$this->path}] could not be opened, empty handle returned.");
            
            $do($img);
        }
        catch (\Throwable $error) {
            throw $error;
        }
        finally {
            if ($img)
                imagedestroy($img);
            restore_error_handler();
        }
    }
}



class SupressOutput extends ContextManager
{
    public function do(callable $do)
    {
        $this->installErrorHandler();
        ob_start();
        try 
        {
            $do();
        } 
        finally {
            ob_end_clean();
        }
    }
}

class CURL extends ContextManager
{
    protected $url;
    
    public function __construct(string $url)
    {
        $this->url = $url;
    }
    
    public function do(callable $do)
    {
        $this->installErrorHandler();
        try {
            $cl = curl_init($this->url);
            $do($cl);
        } 
        finally {
            restore_error_handler();
            curl_close($cl);
        }
    }
}

class PDOTransaction extends ContextManager
{
    protected $pdo;
    
    public function __construct(\PDO $connection)
    {
        $this->pdo = $connection;
    }
    
    public function do(callable $do)
    {
        $this->installErrorHandler();
        try
        {
			$this->pdo->beginTransaction();
			
            $do($this->pdo);
			
			$this->pdo->commit();
        }
        catch (\Exception $error) {
            context::supress_errors()->while(function() {
				$this->pdo->rollback();
			});
            throw $error;
        }
        finally {
            restore_error_handler();
        }
    }
}

class MySQLTransaction extends ContextManager
{
    protected $mysql;
    
    public function __construct(\mysqli $connection)
    {
        $this->mysql = $connection;
    }   
    
    public function do(callable $do)
    {
        $this->installErrorHandler();
        $mysql = null;
        try
        {
			$this->mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
			
            $do($this->mysql);   
			
			$this->mysql->commit(); 
        }
        catch (\Throwable $error) {
            context::supress_errors()->while(function() {
				$this->mysql->rollback();
			});
            throw $error;
        }
        finally {
            restore_error_handler();
        }
    } 
}

class ZipContext extends ContextManager
{
    protected $filePath;
    protected $mode;
    
    public function __construct(string $filePath, $mode = \ZipArchive::CREATE | \ZipArchive::OVERWRITE)
    {
        $this->filePath = $filePath;
        $this->mode = $mode;
    }
    
    public function do(callable $do)
    {
        $errors = [
            \ZipArchive::ER_EXISTS => 'File already exists.',
            \ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
            \ZipArchive::ER_INVAL => 'Invalid argument.',
            \ZipArchive::ER_MEMORY => 'Malloc failure.',
            \ZipArchive::ER_NOENT => 'No such file.',
            \ZipArchive::ER_NOZIP => 'Not a zip archive.',
            \ZipArchive::ER_OPEN => "Can't open file.",
            \ZipArchive::ER_READ => 'Read error.',
            \ZipArchive::ER_SEEK => 'Seek error.'
        ];
        $this->installErrorHandler();
        try {
            $zip = new \ZipArchive;
            $ok = $zip->open($this->filePath, $this->mode);
            if ($ok !== true) {
                throw new \Exception(arrays::get($errors, $ok, 'unknown error'));
            } 
            $do($zip);
        }
        finally {
            if (isset($zip) && $zip instanceof \ZipArchive)
                $zip->close();
            restore_error_handler();
        }
    }
}




