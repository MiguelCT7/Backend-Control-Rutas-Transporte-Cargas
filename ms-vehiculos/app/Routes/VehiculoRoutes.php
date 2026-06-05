<?php

namespace App\Routes;

use App\Controllers\VehiculoController;
use App\Middleware\AuthMiddleware;
use Slim\App;

class VehiculoRoutes
{
    public static function registrar(App $app): void
    {
        // Health debe ir ANTES del grupo para evitar conflicto con /{id}
        $app->get('/api/vehiculos/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success'       => true,
                'microservicio' => 'ms-vehiculos',
                'estado'        => 'activo',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });

        $app->group('/api/vehiculos', function ($group) {
            $group->get('', [VehiculoController::class, 'listar']);
            $group->get('/{id}', [VehiculoController::class, 'obtener']);
            $group->post('', [VehiculoController::class, 'crear']);
            $group->put('/{id}', [VehiculoController::class, 'actualizar']);
            $group->patch('/{id}/estado', [VehiculoController::class, 'cambiarEstado']);
            $group->delete('/{id}', [VehiculoController::class, 'eliminar']);
        })->add(new AuthMiddleware());
    }
}