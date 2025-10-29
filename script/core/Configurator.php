<?php
namespace Slando\core;

class Configurator
{
    public static function load()
    {
        return require_once __DIR__ . '/../config.php';
    }
}