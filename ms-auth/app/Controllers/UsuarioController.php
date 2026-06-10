<?php

namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UsuarioController
{
    /**
     * Lista todos los usuarios registrados.
     */
    public function listar(Request $request, Response $response): Response
    {
        $usuarios = Usuario::select('id', 'nombre', 'correo', 'usuario', 'rol', 'estado', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->responder($response, [
            'success' => true,
            'data'    => $usuarios,
        ]);
    }

    /**
     * Crea un nuevo usuario.
     */
    public function crear(Request $request, Response $response): Response
    {
        $datos   = $request->getParsedBody();
        $errores = $this->validarCampos($datos);

        if (!empty($errores)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Errores de validación.',
                'errores' => $errores,
            ], 422);
        }

        // Verificar si el usuario ya existe
        if (Usuario::where('usuario', trim($datos['usuario']))->exists()) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'El nombre de usuario ya está en uso.',
            ], 409);
        }

        // Verificar si el correo ya existe
        if (Usuario::where('correo', trim($datos['correo']))->exists()) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'El correo ya está registrado.',
            ], 409);
        }

        $usuario = Usuario::create([
            'nombre'     => trim($datos['nombre']),
            'correo'     => trim($datos['correo']),
            'usuario'    => trim($datos['usuario']),
            'contrasena' => trim($datos['contrasena']),
            'rol'        => $datos['rol'] ?? 'operador',
            'estado'     => 'activo',
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Usuario creado correctamente.',
            'data'    => $usuario->only(['id', 'nombre', 'correo', 'usuario', 'rol', 'estado']),
        ], 201);
    }

    /**
     * Cambia el estado de un usuario (activo/inactivo).
     */
    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $usuario = Usuario::find((int) $args['id']);

        if (!$usuario) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Usuario no encontrado.',
            ], 404);
        }

        $usuario->estado = $usuario->estado === 'activo' ? 'inactivo' : 'activo';
        $usuario->save();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Estado actualizado correctamente.',
            'data'    => $usuario->only(['id', 'nombre', 'usuario', 'estado']),
        ]);
    }

    /**
     * Elimina un usuario.
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $usuario = Usuario::find((int) $args['id']);

        if (!$usuario) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Usuario no encontrado.',
            ], 404);
        }

        $usuario->delete();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Usuario eliminado correctamente.',
        ]);
    }

    /**
     * Valida los campos obligatorios.
     */
    private function validarCampos(array $datos): array
    {
        $errores    = [];
        $requeridos = ['nombre', 'correo', 'usuario', 'contrasena'];

        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo '{$campo}' es obligatorio.";
            }
        }

        if (!empty($datos['correo']) && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }

        if (!empty($datos['contrasena']) && strlen($datos['contrasena']) < 4) {
            $errores[] = 'La contraseña debe tener al menos 4 caracteres.';
        }

        return $errores;
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