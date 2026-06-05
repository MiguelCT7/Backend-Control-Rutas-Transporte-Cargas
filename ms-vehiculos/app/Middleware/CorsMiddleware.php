<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            $respuesta = new SlimResponse();
            return $this->agregarCabeceras($respuesta);
        }

        $respuesta = $handler->handle($request);
        return $this->agregarCabeceras($respuesta);
    }

    /**
     * Agrega las cabeceras CORS a la respuesta.
     */
    private function agregarCabeceras(Response $respuesta): Response
    {
        return $respuesta
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}