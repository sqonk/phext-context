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
$ composer require sqonk\phext-context
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


## Examples

Open File handle for writing and output a string. The context manager will open the file and request the appropriate lock.

Once your callback as completed, the file handle will release the lock and close the resouce.

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

// the following block of code throws an exception which is caught and ignored, leaving the program uninterupted.
context::supress_errors()->while(function() {
    println('throwing an exception');
    throw new Exception('This is a test exception.');
});
```

Execute a transaction on a PDO object. The context manager will initiate the transaction before passing off the instance to the callback. Once completed the transaction will be completed. If an exception is raised at any point then the transaction is rolled back.

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