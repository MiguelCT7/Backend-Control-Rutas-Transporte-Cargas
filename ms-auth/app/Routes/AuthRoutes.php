<?php

namespace App\Routes;

use App\Controllers\AuthController;
use Slim\App;

/**
 * Registra todas las rutas del microservicio de autenticación.
 */
class AuthRoutes
{
    public static function registrar(App $app): void
    {
        $app->post('/api/auth/login', [AuthController::class, 'login']);
        $app->post('/api/auth/logout', [AuthController::class, 'logout']);
        $app->post('/api/auth/validar', [AuthController::class, 'validar']);

        // Ruta de salud del microservicio
        $app->get('/api/auth/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success'       => true,
                'microservicio' => 'ms-auth',
                'estado'        => 'activo',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }
}