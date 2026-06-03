<?php

namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador de Autenticación
 * Gestiona el inicio de sesión, cierre de sesión y validación de tokens.
 */
class AuthController
{
    /**
     * Inicia sesión con usuario/correo y contraseña.
     */
    public function login(Request $request, Response $response): Response
    {
        $datos = $request->getParsedBody();

        if (empty($datos['usuario']) || empty($datos['contrasena'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Usuario y contraseña son requeridos.',
            ], 400);
        }

        $identificador = trim($datos['usuario']);
        $contrasena    = trim($datos['contrasena']);

        $usuario = Usuario::where(function ($query) use ($identificador) {
            $query->where('usuario', $identificador)
                  ->orWhere('correo', $identificador);
        })->where('estado', 'activo')->first();

        if (!$usuario || !$usuario->verificarContrasena($contrasena)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Credenciales incorrectas.',
            ], 401);
        }

        $usuario->activarSesion();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Sesión iniciada correctamente.',
            'data'    => [
                'token'   => $usuario->token,
                'nombre'  => $usuario->nombre,
                'correo'  => $usuario->correo,
                'usuario' => $usuario->usuario,
                'rol'     => $usuario->rol,
            ],
        ]);
    }

    /**
     * Cierra la sesión del usuario autenticado.
     */
    public function logout(Request $request, Response $response): Response
    {
        $token = $this->extraerToken($request);

        if (!$token) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Token no proporcionado.',
            ], 400);
        }

        $usuario = Usuario::buscarPorToken($token);

        if (!$usuario) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Sesión no encontrada.',
            ], 404);
        }

        $usuario->cerrarSesion();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Valida si el token de sesión es vigente.
     */
    public function validar(Request $request, Response $response): Response
    {
        $token = $this->extraerToken($request);

        if (!$token) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Token no proporcionado.',
            ], 400);
        }

        $usuario = Usuario::buscarPorToken($token);

        if (!$usuario) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Token inválido o sesión expirada.',
            ], 401);
        }

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Token válido.',
            'data'    => [
                'nombre'  => $usuario->nombre,
                'usuario' => $usuario->usuario,
                'rol'     => $usuario->rol,
            ],
        ]);
    }

    /**
     * Extrae el token del header Authorization.
     */
    private function extraerToken(Request $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        return null;
    }

    /**
     * Construye y devuelve una respuesta JSON.
     */
    private function responder(Response $response, array $datos, int $codigo = 200): Response
    {
        $response->getBody()->write(json_encode($datos, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($codigo);
    }
}