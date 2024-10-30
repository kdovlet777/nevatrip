<?php 

namespace App;

use PDO;
use App\Config;

class DB
{
    private static $dbc; // Хранит экземпляр соединения с базой данных

    /**
     * Устанавливает соединение с базой данных.
     * 
     * Проверяет, существует ли уже соединение. Если нет, создаёт новое соединение
     * с использованием параметров, заданных в файле конфигурации.
     * 
     * @return PDO Возвращает экземпляр PDO для работы с базой данных.
     */
    public static function connection()
    {
        // Если соединение ещё не создано
        if (self::$dbc == NULL) {
            self::$dbc = new PDO(
               "mysql:host=db;dbname=" . Config::get('DB_NAME') . ";charset=utf8", // Строка подключения
               Config::get('DB_USERNAME'), // Имя пользователя из конфигурации
               Config::get('DB_PASSWORD') // Пароль из конфигурации
            );
        }
        
        return self::$dbc; // Возвращает экземпляр PDO
    }
}