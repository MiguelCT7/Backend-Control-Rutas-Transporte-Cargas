<?php

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    private static ?Capsule $capsule = null;

    public static function initialize(): void
    {
        if (self::$capsule !== null) {
            return;
        }

        self::$capsule = new Capsule();

        self::$capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'      => $_ENV['DB_PORT'] ?? '3306',
            'database'  => $_ENV['DB_DATABASE'] ?? 'ms_viajes',
            'username'  => $_ENV['DB_USERNAME'] ?? 'root',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);

        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();
    }
}