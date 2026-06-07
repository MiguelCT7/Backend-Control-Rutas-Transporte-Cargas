<?php

declare(strict_types=1);

use App\Config\Database;
use App\Middleware\CorsMiddleware;
use App\Routes\ViajeRoutes;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

Database::initialize();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->add(new CorsMiddleware());

ViajeRoutes::registrar($app);

$app->run();