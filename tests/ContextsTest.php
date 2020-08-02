<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use sqonk\phext\context\context;

class ContextsTest extends TestCase
{
    public function testFile()
    {
        context::file(dirname(__FILE__).'/samplefile.txt')->do(function($fh) use (&$contents) {
            $contents = fread($fh, 1024);
        });
        
        $this->assertSame('hello world!', $contents);
        
        $this->expectException(RuntimeException::class);
        $this->expectException(ErrorException::class);
        context::file('afilethatdoesnotexist.txt')->do(function($fh) {
            // should not get to this point.
        });
    }
    
    public function testTmpFile()
    {
        context::tmpfile()->do(function($fh) use (&$contents) {
            fwrite($fh, "This is a test");
            rewind($fh);
            $contents = fread($fh, 50);
        });
        $this->assertSame('This is a test', $contents);
    }
    
    public function testFileStream()
    {
        $str = '';
        context::stream(dirname(__FILE__).'/streamtest.txt', 8)->do(function($buffer) use (&$str) {
            $str .= $buffer;
        });
        $this->assertSame('abcdefghijklmnopqrstuvwxyz0123456789', $str);
    }
    
    public function testSupressErrors()
    {
        // here we need to confirm that the artifical exception thrown does not interupt 
        // the flow of the code.
        $flag = false;
        context::supress_errors()->while(function() use (&$flag) {
            $flag = true;
            throw new Exception('test exception');
        });
        
        $this->assertSame(true, $flag);
    }
    
    public function testCurl()
    {
        $url = 'https://sqonk.com/opensource/phext/context/tests/samplefile.txt';
        context::curl($url)->do(function($cl) use (&$contents) {
            curl_setopt($cl, CURLOPT_FRESH_CONNECT, 1);
    		curl_setopt($cl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($cl, CURLOPT_FORBID_REUSE, 1);
            
            $contents = curl_exec($cl);
        });
        
        $this->assertSame('hello world!', $contents);
    }
    
    public function testZip()
    {
        $file = dirname(__FILE__).'/testzip.zip';
        context::zip($file, 0)->do(function($zip) use (&$txt) {
            $txt = $zip->getFromName('hello.txt');
        });
        
        $this->assertSame('hello world!', $txt);
        
        $this->expectException(RuntimeException::class);
        $this->expectException(ErrorException::class);
        context::file('afilethatdoesnotexist.zip')->do(function($zip) {
            // should not get to this point.
        });
    }
}