<?php

use Symfony\Component\Debug\Debug;

$dev_cookie = isset($_COOKIE['enable_dev']) && $_COOKIE['enable_dev'] == 1;
defined("ACORE_DEV_MODE") OR define("ACORE_DEV_MODE", false);


/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/autoload.php';
include_once ACORE_PATH_PLG . '/var/bootstrap.php.cache';

$kernel = null;
if (!ACORE_DEV_MODE) {
    $kernel = new AppKernel('prod', false);
    $kernel->loadClassCache();
} else {
    $kernel = new AppKernel('dev', true);
    Debug::enable();
}


return $kernel;

