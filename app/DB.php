<?php 

namespace App;

use PDO;
use App\Config;

class DB
{
    public static $dbc;
    
    public static function connection()
    {
        if (self::$dbc == NULL) {
            self::$dbc = new PDO(
               "mysql:host=db;dbname=" . Config::get('DB_NAME') . ";charset=utf8",
               Config::get('DB_USERNAME'), 
               Config::get('DB_PASSWORD')
            );
        }
        
        return self::$dbc;
    }
}