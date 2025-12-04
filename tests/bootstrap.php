<?php

use Symfony\Component\ErrorHandler\DebugClassLoader;

require_once __DIR__.'/../vendor/autoload.php';

// ensure a fresh cache every time tests are run
(new Symfony\Component\Filesystem\Filesystem())->remove(__DIR__.'/Fixtures/var/cache/test');

DebugClassLoader::enable();
