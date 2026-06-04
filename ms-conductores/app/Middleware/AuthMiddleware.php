<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Middleware de Autenticación
 * Valida el token contra el microservicio ms-auth.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private string $authServiceUrl;

    public function __construct()
    {
        $this->authServiceUrl = $_ENV['AUTH_SERVICE_URL'] ?? 'http://localhost:8001';
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->rechazar('Token de autenticación requerido.');
        }

        $curl = curl_init("{$this->authServiceUrl}/api/auth/validar");
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: {$authHeader}",
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 5,
        ]);

        $respuestaJson = curl_exec($curl);
        $httpCode      = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200) {
            return $this->rechazar('Sesión inválida o expirada.');
        }

        $datos = json_decode($respuestaJson, true);

        if (!($datos['success'] ?? false)) {
            return $this->rechazar('Token no válido.');
        }

        $request = $request->withAttribute('usuario', $datos['data'] ?? []);

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