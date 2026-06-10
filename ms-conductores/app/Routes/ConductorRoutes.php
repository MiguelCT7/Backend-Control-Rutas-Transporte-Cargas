<?php

namespace App\Routes;

use App\Controllers\ConductorController;
use App\Middleware\AuthMiddleware;
use Slim\App;

class ConductorRoutes
{
    public static function registrar(App $app): void
    {
        // Health debe ir primero
        $app->get('/api/conductores/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success'       => true,
                'microservicio' => 'ms-conductores',
                'estado'        => 'activo',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // Licencias por vencer debe ir antes de /{id}
        $app->get('/api/conductores/licencias-por-vencer',
            [ConductorController::class, 'licenciasPorVencer']
        );

        $app->group('/api/conductores', function ($group) {
            $group->get('', [ConductorController::class, 'listar']);
            $group->get('/{id}', [ConductorController::class, 'obtener']);
            $group->post('', [ConductorController::class, 'crear']);
            $group->put('/{id}', [ConductorController::class, 'actualizar']);
            $group->patch('/{id}/estado', [ConductorController::class, 'cambiarEstado']);
            $group->delete('/{id}', [ConductorController::class, 'eliminar']);
        })->add(new AuthMiddleware());
    }
}