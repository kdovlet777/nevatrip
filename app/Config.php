<?php

namespace App;

use Dotenv\Dotenv;

class Config
{
    private static $data;

    public static function get($args, $default = null)
    {
        if (self::$data === null) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
            self::$data = $_ENV;
        }

        return (self::$data[$args] ?? $default);
    }
}