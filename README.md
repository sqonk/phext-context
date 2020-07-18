# PHEXT Context

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg)](https://php.net/)
[![License](https://sqonk.com/opensource/license.svg)](license.txt)

Contexts create a block level scope on a resource and automatically manage the creation and cleanup of that resource irrespective of any exceptions that arise while in use, the primary intention being to help remove the possibility of resource leaks. On top of that they aid in keeping your code lean by automating the standard logic involved with operating such resources.

They are a phsuedo implementation of the context managers in Python. While PHP 7 and earlier do not currently allow for a 1:1 elegant solution, the below intepretation makes use of function callbacks to achieve something similar.

While alternative implementations make clever use of generators and 'yield' to push the resources to a 1-cycle foreach loop, the readability is somewhat lost when you follow the code.

This implementation attempts to keep the code readable and be self-explanatory as to what is happening when being studied by another person, even if it is a little more verbose.

In many of the methods a special dedicated error handler is temporarily injected to convert PHP errors into raised Exceptions. Keep in mind that while inside a context manager any standard error handling systems your code is using will be overriden until the manager exits back into to the parent scope.


## About PHEXT

The PHEXT package is a set of libraries for PHP that aim to solve common problems with a syntax that helps to keep your code both concise and readable.

PHEXT aims to not only be useful on the web SAPI but to also provide a productivity boost to command line scripts, whether they be for automation, data analysis or general research.

## Install

Via Composer

``` bash
$ composer require sqonk/phext-context
```


Context Features
----------------

Out of the box the library supports the following managers for:

* Files
* Streams
* GD Images
* MySQL Transactions
* PDO Transactions
* Error/Exception supression
* Output buffer supression
* Zip Files
* CURL



Available Methods
-----------------

```php
use \sqonk\phext\context\context;
```



##### file

```php
static public function file(string $filePath, string $mode = 'r')
```

Open a file in the desired mode and then pass it to the callback. The callback should accept one parameter, which is the file handle (resource).

Exceptions and errors can be thrown but the file will be safely closed off.

Example:

```php
// output some sample text to the file 'out.txt'.
context::file('out.txt', 'w')->do(function($fh) {
    fwrite($fh, "This is a test");
});
```



##### tmpfile

```php
static public function tmpfile()
```

Open a temporary file and pass it to the callback. The callback should accept one parameter, which is the file handle (resource).

Exceptions and errors can be thrown but the file will be safely closed off.

Example:

```php
// output some sample text to the temp file 'out.txt'.
context::tmpfile()->do(function($fh) {
    fwrite($fh, "This is a test");
    rewind($fh);
    println('contents:', fread($fh, 50));
});
```



  

##### stream

```php
static public function stream(string $filePath, int $chunkMultiplier = 1024)
```

Open a file in 'read' mode and download the contents in chunks, passing each chunk to the callback as it is received. 

The default read chunk size is 1024 * 1024, which can be adjusted by passing in your own chunk multiplier. Just be aware that what ever value you pass in will be squared to form the final chunk size.

This method uses a file context as its parent context manager and thus does not introduce any further exception handling.

Example:

```php
context::stream('path/to/large/file.mov')->do(function($buffer) {
    println(strlen($buffer)); // print out each chunk of data read from the input stream.
});
```



##### image

```php
static public function image(string $filePath)
```

Open a image resource (using GD) and pass it to the callback. The callback should accept just one parameter: the image resouce.

Example:

```php
/*
		Open an image, modify it and output the result.
*/
context::image('/path/to/image.jpg')->do(function($img) {
    # greyscale
    imagefilter($img, IMG_FILTER_GRAYSCALE);
    
    # pixelate
    imagefilter($img, IMG_FILTER_PIXELATE, 8, IMG_FILTER_PIXELATE);
    
    # output result
    imagepng($img, 'modifiedImage.png');
});
```



##### supress_errors

```php
static public function supress_errors()
```

Perform a block of code in the callback and ignore all possible errors and exceptions that occur. 

Example:

```php
/*
    The following block of code throws an exception which is caught 
    and ignored, leaving the program uninterupted.

    Also note the use of while() on the callback. 'while' is an alias of 'do'
    and with some context managers makes more syntactic sense.
*/
context::supress_errors()->while(function() {
    println('throwing an exception');
    throw new Exception('This is a test exception.');
});
```



##### no_output

```php
static public function no_output()
```

Perform a block of code while preventing any output to std out (console in CLI SAPI or the browser for the web.)



##### pdo_transaction

```php
static public function pdo_transaction(\PDO $connection)
```

Execute and attempt to commit a PDO database transaction. If an error is thrown at any point the transaction will be rolled back.

Example:

```php
/*
		Execute a transaction on a PDO object. The context manager will initiate 
		the transaction before passing off the instance to the callback. Once completed 
		the transaction will be comitted. 

		If an exception is raised at any point then the transaction is rolled back.
*/
$pdo = ... // your PDO instance, set up as required.

context::pdo_transaction($pdo)->do(function($pdo) {
    // perform operations on the pdo instance.
});
```



##### mysql_transaction

```php
static publlic function mysql_transaction(\mysqli $connection)
```

Execute and attempt to commit a MySQL database transaction. If an error is thrown at any point the transaction will be rolled back.



##### curl

```php
static public function curl(string $url = '')
```

Initialise a cURL handle. This curl handle is set to the given URL but no further options are set.



##### zip

``` php
static public function zip(string $filePath, $mode = \ZipArchive::CREATE | \ZipArchive::OVERWRITE)
```

Open a zip file at the specified location and in the desired mode, then pass it to the callback. The callback  should accept one parameter, which is the zip handle (resource).

The default behaviour is to open or create a zip archive for outputting data to. The mode can be changed by passing in the relevant ZipArchive constant.

Exceptions and errors will be thrown but the file will be safely closed off.



## Credits

Theo Howell

## License

The MIT License (MIT). Please see [License File](license.txt) for more information.