<?php

namespace ACore\Lib;

abstract class WpClass {

    /**
     * Used to get a static prefix for wp action and filters
     */
    public static function sprefix()
    {
        return static::class . "::";
    }
}
