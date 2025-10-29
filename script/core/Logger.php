<?php
namespace Slando\core;

class Logger {
    public static function log($message)
    {
        file_put_contents(__DIR__ . '/../../logs/' . date('Ymd', time()) . '.log',
            date('Y-m-d H:i:s ') . $message . PHP_EOL, FILE_APPEND);
    }
}