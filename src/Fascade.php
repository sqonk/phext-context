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
    The context class acts as a gateway between the context manage objects and your code, essentially
	adding a functional interface atop of the object orientated base.
*/

class context
{
	/* 
	    Open a file in the desired mode and then pass it to the callback. The callback 
	    should accept one parameter, which is the file handle (resource).

	    Exceptions and errors will be thrown but the file will be safely closed off.
	*/
	static public function file(string $filePath, string $mode = 'r')
	{
	    return new FileHandle($filePath, $mode);
	}

	/*
	    Open a image resource (using GD) and pass it to the callback. The callback 
	    should accept just one parameter: the image resouce.
	*/
	static public function image(string $filePath)
	{
	    return new Image($filePath);
	}

	/*
	    Perform a block of code in the callback and ignore or all possible errors
	    and exceptions that occur. 
	*/
	static public function supress_errors()
	{
	    return new SupressErrors();
	}

	/*
	    Perform a block of code while preventing any output to STDOut (console in 
	    CLI SAPI or the browser for the web.)
	*/
	static public function no_output()
	{
	    return new NoOutput();
	}

	/*
	    Execute and attempt to commit a PDO database transaction. If an error is thrown at any point
		the transaction will be rolled back.
	*/
	static public function pdo_transaction(\PDO $connection)
	{
	    return new PDOTransaction($connection);
	}

	/*
	    Execute and attempt to commit a MySQL database transaction. If an error is thrown at any point
		the transaction will be rolled back.
	*/
	static public function mysql_transaction(\mysqli $connection)
	{
	    return new MySQLTransaction($connection);
	}

	/*
	    Initialise a cURL handle. This curl handle is set to the given URL but no further
	    options are set.

	    NOTE:   If you want to perform a simple GET or POST request without much effort, without 
	            need for customisation, you may be better off using the network utility class 
	            in the core package.
	*/
	static public function curl(string $url = '')
	{
	    return new CURL($url);
	}

	/*
	    Open a file in 'read' mode and download the contents in chunks, passing each chunk
	    to the callback as it is received. 

	    The default read chunk size is 1024 * 1024, which can be adjusted by passing in your
	    own chunk multiplier. Just be aware that what ever value you pass in will be squared
	    to form the final chunk size.

	    This method uses a file context as its parent context manager and thus does not introduce
	    any further exception handling.
	*/
	static public function stream(string $filePath, int $chunkMultiplier = 1024)
	{
	    return new StreamHandle($filePath, $chunkMultiplier);
	}

	/*
	    Open a zip file at the specified location and in the desired mode, then pass it to the callback. The callback 
	    should accept one parameter, which is the zip handle (resource).

	    The default behaviour is to open or create a zip archive for outputting data to. The mode can be changed
	    by passing in the relevant ZipArchive constant.

	    Exceptions and errors will be thrown but the file will be safely closed off.
	*/
	static public function zip(string $filePath, $mode = \ZipArchive::CREATE | \ZipArchive::OVERWRITE)
	{
	    return new ZipContext($filePath, $mode);
	}
}