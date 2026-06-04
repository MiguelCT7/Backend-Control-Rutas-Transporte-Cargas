<?php

namespace App\Routes;

use App\Controllers\ConductorController;
use App\Middleware\AuthMiddleware;
use Slim\App;

class ConductorRoutes
{
    public static function registrar(App $app): void
    {
        $app->get('/api/conductores/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success'       => true,
                'microservicio' => 'ms-conductores',
                'estado'        => 'activo',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });

        $app->get('/api/conductores', [ConductorController::class, 'listar'])->add(new AuthMiddleware());
        $app->get('/api/conductores/{id}', [ConductorController::class, 'obtener'])->add(new AuthMiddleware());
        $app->post('/api/conductores', [ConductorController::class, 'crear'])->add(new AuthMiddleware());
        $app->put('/api/conductores/{id}', [ConductorController::class, 'actualizar'])->add(new AuthMiddleware());
        $app->delete('/api/conductores/{id}', [ConductorController::class, 'eliminar'])->add(new AuthMiddleware());
    }
}