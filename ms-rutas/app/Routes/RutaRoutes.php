<?php

namespace App\Routes;

use App\Controllers\RutaController;
use App\Controllers\ProgramacionController;
use App\Middleware\AuthMiddleware;
use Slim\App;

class RutaRoutes
{
    public static function registrar(App $app): void
    {
        // Health debe ir primero
        $app->get('/api/rutas/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success'       => true,
                'microservicio' => 'ms-rutas',
                'estado'        => 'activo',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Rutas
        $app->group('/api/rutas', function ($group) {
            $group->get('', [RutaController::class, 'listar']);
            $group->get('/{id}', [RutaController::class, 'obtener']);
            $group->post('', [RutaController::class, 'crear']);
            $group->put('/{id}', [RutaController::class, 'actualizar']);
            $group->delete('/{id}', [RutaController::class, 'eliminar']);
        })->add(new AuthMiddleware());

        // Programaciones
        $app->group('/api/programaciones', function ($group) {
            $group->get('', [ProgramacionController::class, 'listar']);
            $group->get('/{id}', [ProgramacionController::class, 'obtener']);
            $group->post('', [ProgramacionController::class, 'crear']);
            $group->put('/{id}', [ProgramacionController::class, 'actualizar']);
        })->add(new AuthMiddleware());
    }
}