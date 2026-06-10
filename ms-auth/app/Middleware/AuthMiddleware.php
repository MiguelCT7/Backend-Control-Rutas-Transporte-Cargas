<?php

namespace App\Middleware;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->rechazar('Token de autenticación requerido.');
        }

        $token   = substr($authHeader, 7);
        $usuario = Usuario::buscarPorToken($token);

        if (!$usuario) {
            return $this->rechazar('Token inválido o sesión expirada.');
        }

        $request = $request->withAttribute('usuario', $usuario);

        return $handler->handle($request);
    }

    private function rechazar(string $mensaje): Response
    {
        $respuesta = new SlimResponse();
        $respuesta->getBody()->write(json_encode([
            'success' => false,
            'mensaje' => $mensaje,
        ], JSON_UNESCAPED_UNICODE));

        return $respuesta
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}