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

``` php
/* 
    Open a file in the desired mode and then pass it to the callback. The callback 
    should accept one parameter, which is the file handle (resource).

    Exceptions and errors will be thrown but the file will be safely closed off.
*/
context::file(string $filePath, string $mode = 'r');

/*
    Open a image resource (using GD) and pass it to the callback. The callback 
    should accept just one parameter: the image resouce.
*/
context::image(string $filePath);

/*
    Perform a block of code in the callback and ignore or all possible errors
    and exceptions that occur. 
*/
context::supress_errors();

/*
    Perform a block of code while preventing any output to STDOut (console in 
    CLI SAPI or the browser for the web.)
*/
context::no_output();

/*
    Execute and attempt to commit a PDO database transaction. If an error is thrown at 
    any point the transaction will be rolled back.
*/
context::pdo_transaction(\PDO $connection);

/*
    Execute and attempt to commit a MySQL database transaction. If an error is thrown at 
    any point the transaction will be rolled back.
*/
context::mysql_transaction(\mysqli $connection);

/*
    Initialise a cURL handle. This curl handle is set to the given URL but no further
    options are set.

    NOTE:   If you want to perform a simple GET or POST request without much effort, without 
            need for customisation, you may be better off using the network utility class 
            in the core package.
*/
context::curl(string $url = '');

/*
    Open a file in 'read' mode and download the contents in chunks, passing each chunk
    to the callback as it is received. 

    The default read chunk size is 1024 * 1024, which can be adjusted by passing in your
    own chunk multiplier. Just be aware that what ever value you pass in will be squared
    to form the final chunk size.

    This method uses a file context as its parent context manager and thus does not introduce
    any further exception handling.
*/
context::stream(string $filePath, int $chunkMultiplier = 1024);

/*
    Open a zip file at the specified location and in the desired mode, then pass 
    it to the callback. The callback  should accept one parameter, which is the 
    zip handle (resource).

    The default behaviour is to open or create a zip archive for outputting data to. 
    The mode can be changed by passing in the relevant ZipArchive constant.

    Exceptions and errors will be thrown but the file will be safely closed off.
*/
context::zip(string $filePath, $mode = \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
```

## Examples

Open a file handle for writing and output a string. The context manager will open the file and request the appropriate lock.

Once your callback has completed, the file handle will release the lock and close the resouce.

``` php
use sqonk\phext\context\context;

context::file('out.txt', 'w')->do(function($fh) {
    fwrite($fh, "This is a test");
    fflush($fh);
});
```

Similar to a file context, stream can be used for reading in chunks of a large file.

``` php
use sqonk\phext\context\context;

context::stream('path/to/large/file.mov')->do(function($buffer) {
    println(strlen($buffer)); // print out the chunk of data read from the input stream.
});
```

Supress all exceptions and errors while executing a block of code:

``` php
use sqonk\phext\context\context;

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

Execute a transaction on a PDO object. The context manager will initiate the transaction before passing off the instance to the callback. Once completed the transaction will be comitted. 

If an exception is raised at any point then the transaction is rolled back.

``` php
use sqonk\phext\context\context;

$pdo = ... // your PDO instance, set up as required.

context::pdo_transaction($pdo)->do(function($pdo) {
    // perform operations on the pdo instance.
});

```

Open an image, modify it and output the result.

``` php
use sqonk\phext\context\context;

context::image('/path/to/image.jpg')->do(function($img) {
    # greyscale
    imagefilter($img, IMG_FILTER_GRAYSCALE);
    
    # pixelate
    imagefilter($img, IMG_FILTER_PIXELATE, 8, IMG_FILTER_PIXELATE);
    
    # output result
    imagepng($img, 'modifiedImage.png');
});

```

## Credits

Theo Howell
 
## License

The MIT License (MIT). Please see [License File](license.txt) for more information.