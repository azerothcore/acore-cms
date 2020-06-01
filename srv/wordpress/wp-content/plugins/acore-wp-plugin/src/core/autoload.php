<?php

if (!defined("WPINC"))
    require(__DIR__."/../../../../../wp-load.php");

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/** @var ClassLoader $loader */
$loader = require ACORE_PATH_PLG . '/vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
