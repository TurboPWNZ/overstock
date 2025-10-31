<?php
namespace Slando\core;

class Configurator
{
    private static $_config;

    public static function load()
    {
        if (empty(self::$_config))
            self::$_config = require_once __DIR__ . '/../config.php';

        return self::$_config;
    }
}