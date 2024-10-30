<?php

namespace App;

use Dotenv\Dotenv;

class Config
{
    private static $data; // Хранит конфигурационные данные

    /**
     * Получает значение конфигурационного параметра.
     * 
     * Загружает переменные окружения из файла `.env`, если они ещё не загружены.
     * 
     * @param string $args Имя конфигурационного параметра.
     * @param mixed $default Значение по умолчанию, если параметр не найден.
     * 
     * @return mixed Возвращает значение конфигурационного параметра или значение по умолчанию.
     */
    public static function get($args, $default = null)
    {
        // Если данные ещё не загружены
        if (self::$data === null) {
            // Загружает переменные окружения
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
            self::$data = $_ENV; // Сохраняет загруженные данные
        }

        // Возвращает значение параметра или значение по умолчанию
        return (self::$data[$args] ?? $default);
    }
}