<?php

require_once './vendor/autoload.php';

use Utopia\CLI\CLI;
use Utopia\Validator\Wildcard;

$cli = new CLI();

$cli
    ->task('build')
    ->param('read', null, new Wildcard(), 'Valid email address')
    ->param('write', null, new Wildcard(), 'Valid email address')
    ->action(function ($read, $write) {
        // var_dump("Starting program\n");
        var_dump($read);
        var_dump($write);
    });

$cli->run();