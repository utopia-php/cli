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

And then, run from command line:

```bash
php script.php command-name --email=me@example.com
```

### Simple Prompts 

Each parameter in the Utopia CLI task can be passed in 3 ways in the following order of precedence:
* Command line ( --param=value ) 
* Prompt 
* Default value

If a parameter is not passed using the **command line**, a pre-configured **prompt will be displayed**. If the prompt is not configured, the **default value** will be used. 

In the previous email example, we can enable prompts by adding the following to the `script.php` file:

```php
<?php

require_once './vendor/autoload.php';

use Utopia\CLI\CLI;
use Utopia\CLI\Console;
use Utopia\Validator\Email;

$cli = new CLI();

$cli
    ->task('command-name')
    ->param('email', '', new Wildcard(), '', "Please enter your email\n")
    ->action(function ($email) {
        Console::success($email);
    });

$cli->run();
```
And then, run from command line:

```bash
php script.php command-name 
Please enter your email
hello@world.com   
```

That's how you create a text prompt 👏. 

### Single Select / Multi Select Prompts 

You can also create a prompt that allows you to select a single value or multiple values by providing `options` and a value ( `$numSelect` ) indicating the maximum number of values that can be selected.

```php
<?php
require_once './vendor/autoload.php';

use Utopia\CLI\CLI;
use Utopia\CLI\Console;
use Utopia\Validator\Email;

$cli = new CLI();

$emails = [
    "john@doe.com" => "John Doe",
    "hello@world.com" => "Hello World"
];

$cli
    ->task('command-name')
    ->param('email', '', new Wildcard(), '', "Please select your email ID\n", $emails, 1, false)
    ->action(function ($email) {
        var_dump($email);
    });

$cli->run();
```

And then, run from command line:

```bash
php script.php command-name 

Please select your email ID
[○] John Doe ( john@doe.com )
[●] Hello World ( hello@world.com ) <-
array(1) {
  ["hello@world.com"]=>
  string(11) "Hello World"
}
```

Creating multi-select prompts follows a similar process. Just increase the value of `$numSelect` to 2 or more.



```php
<?php
require_once './vendor/autoload.php';

use Utopia\CLI\CLI;
use Utopia\CLI\Console;
use Utopia\Validator\Email;

$cli = new CLI();

$emails = [
    "john@doe.com" => "John Doe",
    "hello@world.com" => "Hello World",
    "lorem@ipsum.com" => "Lorem Ipsum"
];

$cli
    ->task('command-name')
    ->param('email', '', new Wildcard(), '', "Please select your email IDs ( upto 2 )\n", $emails, 2, false)
    ->action(function ($email) {
        var_dump($email);
    });

$cli->run();
```

And then, run from command line:

```bash
php script.php command-name 

Please select your email IDs ( upto 2 )
[✔] John Doe ( john@doe.com )
[ ] Hello World ( hello@world.com )
[✔] Lorem Ipsum ( lorem@ipsum.com ) <-
array(2) {
  ["john@doe.com"]=>
  string(8) "John Doe"
  ["lorem@ipsum.com"]=>
  string(11) "Lorem Ipsum"
}
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
