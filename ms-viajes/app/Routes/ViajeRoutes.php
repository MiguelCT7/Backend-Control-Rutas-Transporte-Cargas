<?php

namespace App\Routes;

use App\Controllers\ViajeController;
use App\Middleware\AuthMiddleware;
use Slim\App;

class ViajeRoutes
{
    public static function registrar(App $app): void
    {
        // Health debe ir primero
        $app->get('/api/viajes/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success'       => true,
                'microservicio' => 'ms-viajes',
                'estado'        => 'activo',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });

        $app->group('/api/viajes', function ($group) {
            $group->get('', [ViajeController::class, 'listar']);
            $group->get('/historial/{programacion_id}', [ViajeController::class, 'historial']);
            $group->post('/iniciar', [ViajeController::class, 'iniciar']);
            $group->post('/estado', [ViajeController::class, 'actualizarEstado']);
            $group->post('/novedad', [ViajeController::class, 'registrarNovedad']);
            $group->post('/finalizar', [ViajeController::class, 'finalizar']);
        })->add(new AuthMiddleware());
    }
}