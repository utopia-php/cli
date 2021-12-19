# Utopia CLI

[![Build Status](https://travis-ci.org/utopia-php/cli.svg?branch=master)](https://travis-ci.com/utopia-php/cli)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/cli.svg)
[![Discord](https://img.shields.io/discord/564160730845151244)](https://appwrite.io/discord)

Utopia framework CLI library is simple and lite library for extending Utopia PHP Framework to be able to code command line applications. This library is aiming to be as simple and easy to learn and use. This library is maintained by the [Appwrite team](https://appwrite.io).

Although this library is part of the [Utopia Framework](https://github.com/utopia-php/framework) project it is dependency free and can be used as standalone with any other PHP project or framework.

## Getting Started

Install using composer:
```bash
composer require utopia-php/cli
```

script.php
```php
<?php

require_once './vendor/autoload.php';

use Utopia\CLI\CLI;
use Utopia\CLI\Console;
use Utopia\Validator\Email;

$cli = new CLI();

$cli
    ->task('command-name')
    ->param('email', null, new Email())
    ->action(function ($email) {
        Console::success($email);
    });

$cli->run();

```

And than, run from command line:

```bash
php script.php command-name --email=me@example.com
```

### Log Messages

```php
Console::log('Plain Log'); // stdout
```

```php
Console::success('Green log message'); // stdout
```

```php
Console::info('Blue log message'); // stdout
```

```php
Console::warning('Yellow log message'); // stderr
```

```php
Console::error('Red log message'); // stderr
```

### Execute Commands

Function returns exit code (0 - OK, >0 - error) and writes stdout, stderr to reference variables. The timeout variable allows you to limit the number of seconds the command can run.



```php
$stdout = '';
$stderr = '';
$stdin = '';
$timeout = 3; // seconds
$code = Console::execute('>&1 echo "success"', $stdin, $stdout, $stderr, $timeout);

echo $code; // 0
echo $stdout; // 'success'
echo $stderr; // ''
```

```php
$stdout = '';
$stderr = '';
$stdin = '';
$timeout = 3; // seconds
$code = Console::execute('>&2 echo "error"', $stdin, $stdout, $stderr, $timeout);

echo $code; // 0
echo $stdout; // ''
echo $stderr; // 'error'
```

### Create a Daemon

You can use the `Console::loop` command to create your PHP daemon. The `loop` method already handles CPU consumption using a configurable sleep function and calls the PHP garbage collector every 5 minutes.

```php
<?php

use Utopia\CLI\Console;

include './vendor/autoload.php';

Console::loop(function() {
    echo "Hello World\n";
}, 200000 /* 200ms */);
```

## System Requirements

Utopia Framework requires PHP 7.4 or later. We recommend using the latest PHP version whenever possible.

## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
