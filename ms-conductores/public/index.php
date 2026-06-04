<?php

declare(strict_types=1);

use App\Config\Database;
use App\Middleware\CorsMiddleware;
use App\Routes\ConductorRoutes;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Inicializar base de datos con Eloquent ORM
Database::initialize();

// Crear aplicación Slim
$app = AppFactory::create();

// Registrar middleware global
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(new CorsMiddleware());

// Registrar rutas
ConductorRoutes::registrar($app);

$app->run();