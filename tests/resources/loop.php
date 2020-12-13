<?php

use Utopia\CLI\Console;

include __DIR__.'/../../vendor/autoload.php';

Console::loop(function() {
    echo "Hello\n";
});