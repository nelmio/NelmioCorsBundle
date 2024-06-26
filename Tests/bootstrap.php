<?php

use Composer\Autoload\ClassLoader;

function includeIfExists(string $file): ?ClassLoader
{
    return file_exists($file) ? include $file : null;
}

if ((!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php')) && (!$loader = includeIfExists(__DIR__.'/../../../../../autoload.php'))) {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}
