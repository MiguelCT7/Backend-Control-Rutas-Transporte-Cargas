<?php

namespace App\Controllers;

use App\Models\ProgramacionViaje;
use App\Models\Ruta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProgramacionController
{
    /**
     * Lista todas las programaciones con filtros opcionales.
     */
    public function listar(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query  = ProgramacionViaje::with('ruta');

        if (!empty($params['conductor_id'])) $query->where('conductor_id', $params['conductor_id']);
        if (!empty($params['vehiculo_id']))  $query->where('vehiculo_id', $params['vehiculo_id']);
        if (!empty($params['estado']))       $query->where('estado', $params['estado']);
        if (!empty($params['fecha_salida'])) $query->where('fecha_salida', $params['fecha_salida']);

        $programaciones = $query->orderBy('fecha_salida', 'asc')->get();

        return $this->responder($response, [
            'success' => true,
            'data'    => $programaciones,
        ]);
    }

    /**
     * Obtiene una programación por su ID.
     */
    public function obtener(Request $request, Response $response, array $args): Response
    {
        $programacion = ProgramacionViaje::with('ruta')->find((int) $args['id']);

        if (!$programacion) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Programación no encontrada.',
            ], 404);
        }

        return $this->responder($response, [
            'success' => true,
            'data'    => $programacion,
        ]);
    }

    /**
     * Crea una nueva programación de viaje.
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

        if (!Ruta::find((int) $datos['ruta_id'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'La ruta seleccionada no existe.',
            ], 404);
        }

        $programacion = ProgramacionViaje::create([
            'conductor_id'           => (int) $datos['conductor_id'],
            'vehiculo_id'            => (int) $datos['vehiculo_id'],
            'ruta_id'                => (int) $datos['ruta_id'],
            'fecha_salida'           => $datos['fecha_salida'],
            'hora_salida'            => $datos['hora_salida'],
            'fecha_estimada_llegada' => $datos['fecha_estimada_llegada'],
            'observaciones'          => $datos['observaciones'] ?? null,
            'estado'                 => 'programado',
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Viaje programado correctamente.',
            'data'    => $programacion->load('ruta'),
        ], 201);
    }

    /**
     * Actualiza una programación existente.
     */
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        $programacion = ProgramacionViaje::find((int) $args['id']);

        if (!$programacion) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Programación no encontrada.',
            ], 404);
        }

        $datos = $request->getParsedBody();

        if (isset($datos['fecha_salida']))           $programacion->fecha_salida           = $datos['fecha_salida'];
        if (isset($datos['hora_salida']))             $programacion->hora_salida             = $datos['hora_salida'];
        if (isset($datos['fecha_estimada_llegada'])) $programacion->fecha_estimada_llegada  = $datos['fecha_estimada_llegada'];
        if (isset($datos['conductor_id']))            $programacion->conductor_id            = (int) $datos['conductor_id'];
        if (isset($datos['vehiculo_id']))             $programacion->vehiculo_id             = (int) $datos['vehiculo_id'];
        if (array_key_exists('observaciones', $datos)) $programacion->observaciones         = $datos['observaciones'];

        $programacion->save();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Programación actualizada correctamente.',
            'data'    => $programacion->load('ruta'),
        ]);
    }

    /**
     * Valida los campos obligatorios de la programación.
     */
    private function validarCampos(array $datos): array
    {
        $errores    = [];
        $requeridos = [
            'conductor_id', 'vehiculo_id', 'ruta_id',
            'fecha_salida', 'hora_salida', 'fecha_estimada_llegada',
        ];

        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo '{$campo}' es obligatorio.";
            }
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