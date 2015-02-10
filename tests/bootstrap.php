<?php

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require $file;
$loader->setPsr4('TreeHouse\\Feeder\\Tests\\', __DIR__ . '/TreeHouse/Feeder/Tests');
