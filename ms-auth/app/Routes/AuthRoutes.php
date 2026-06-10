<?php

namespace App\Routes;

use App\Controllers\AuthController;
use App\Controllers\UsuarioController;
use App\Middleware\AuthMiddleware;
use Slim\App;

class AuthRoutes
{
    public static function registrar(App $app): void
    {
        // ── Auth ─────────────────────────────────────────────────────────────
        $app->post('/api/auth/login', [AuthController::class, 'login']);
        $app->post('/api/auth/logout', [AuthController::class, 'logout']);
        $app->post('/api/auth/validar', [AuthController::class, 'validar']);

        $app->get('/api/auth/health', function ($request, $response) {
            $response->getBody()->write(json_encode([
                'success'       => true,
                'microservicio' => 'ms-auth',
                'estado'        => 'activo',
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        });

        // ── Usuarios ──────────────────────────────────────────────────────────
        $app->group('/api/usuarios', function ($group) {
            $group->get('', [UsuarioController::class, 'listar']);
            $group->post('', [UsuarioController::class, 'crear']);
            $group->patch('/{id}/estado', [UsuarioController::class, 'cambiarEstado']);
            $group->delete('/{id}', [UsuarioController::class, 'eliminar']);
        })->add(new AuthMiddleware());
    }
}