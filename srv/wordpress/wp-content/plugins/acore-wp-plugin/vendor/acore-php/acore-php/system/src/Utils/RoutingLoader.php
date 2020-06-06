<?php

/* Uncomment if you want to use
namespace ACore\System;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

abstract class RoutingLoader extends Loader {
    private $loaded = false;

    public function load($import,$resource, $type = null) {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $app = $this->import($import, 'annotation');
        $app->addPrefix("/{prefix}/");
        $app->addDefaults(array("prefix" => "def"));

        $routeCollection = new RouteCollection();

        $routeCollection->addCollection($app);

        $this->loaded = true;

        return $routeCollection;
    }
}
*/