<?php

namespace App\Controllers;

use App\Models\SeguimientoViaje;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ViajeController
{
    /**
     * Lista todos los seguimientos con filtros opcionales.
     */
    public function listar(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query  = SeguimientoViaje::query();

        if (!empty($params['programacion_id'])) {
            $query->where('programacion_viaje_id', $params['programacion_id']);
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $seguimientos = $query->orderBy('fecha', 'desc')->orderBy('hora', 'desc')->get();

        return $this->responder($response, [
            'success' => true,
            'data'    => $seguimientos,
        ]);
    }

    /**
     * Obtiene el historial completo de un viaje.
     */
    public function historial(Request $request, Response $response, array $args): Response
    {
        $programacionId = (int) $args['programacion_id'];
        $historial      = SeguimientoViaje::historial($programacionId);

        return $this->responder($response, [
            'success'         => true,
            'programacion_id' => $programacionId,
            'data'            => $historial,
        ]);
    }

    /**
     * Inicia un viaje programado.
     */
    public function iniciar(Request $request, Response $response): Response
    {
        $datos = $request->getParsedBody();

        if (empty($datos['programacion_viaje_id'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Se requiere el ID de programación.',
            ], 400);
        }

        $programacionId = (int) $datos['programacion_viaje_id'];
        $estadoActual   = SeguimientoViaje::ultimoEstado($programacionId);

        if ($estadoActual === 'cancelado') {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'No se puede iniciar un viaje cancelado.',
            ], 422);
        }

        if ($estadoActual === 'en_transito') {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'El viaje ya fue iniciado.',
            ], 422);
        }

        $seguimiento = SeguimientoViaje::create([
            'programacion_viaje_id' => $programacionId,
            'fecha'                 => date('Y-m-d'),
            'hora'                  => date('H:i:s'),
            'estado'                => 'en_transito',
            'novedad'               => $datos['novedad'] ?? 'Viaje iniciado.',
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Viaje iniciado.',
            'data'    => $seguimiento,
        ], 201);
    }

    /**
     * Actualiza el estado de un viaje.
     */
    public function actualizarEstado(Request $request, Response $response): Response
    {
        $datos   = $request->getParsedBody();
        $estados = ['programado', 'en_transito', 'retrasado', 'finalizado', 'cancelado'];

        if (empty($datos['programacion_viaje_id']) || empty($datos['estado'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'programacion_viaje_id y estado son requeridos.',
            ], 400);
        }

        if (!in_array($datos['estado'], $estados)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Estado inválido. Permitidos: ' . implode(', ', $estados),
            ], 422);
        }

        $programacionId = (int) $datos['programacion_viaje_id'];
        $estadoActual   = SeguimientoViaje::ultimoEstado($programacionId);

        if ($datos['estado'] === 'finalizado' && !in_array($estadoActual, ['en_transito', 'retrasado'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Solo se puede finalizar un viaje en tránsito o retrasado.',
            ], 422);
        }

        $seguimiento = SeguimientoViaje::create([
            'programacion_viaje_id' => $programacionId,
            'fecha'                 => date('Y-m-d'),
            'hora'                  => date('H:i:s'),
            'estado'                => $datos['estado'],
            'novedad'               => $datos['novedad'] ?? null,
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Estado actualizado.',
            'data'    => $seguimiento,
        ], 201);
    }

    /**
     * Registra una novedad del viaje.
     */
    public function registrarNovedad(Request $request, Response $response): Response
    {
        $datos = $request->getParsedBody();

        if (empty($datos['programacion_viaje_id']) || empty($datos['novedad'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'programacion_viaje_id y novedad son requeridos.',
            ], 400);
        }

        $estadoActual = SeguimientoViaje::ultimoEstado((int) $datos['programacion_viaje_id']);

        if (!$estadoActual) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'No se encontró la programación.',
            ], 404);
        }

        $seguimiento = SeguimientoViaje::create([
            'programacion_viaje_id' => (int) $datos['programacion_viaje_id'],
            'fecha'                 => date('Y-m-d'),
            'hora'                  => date('H:i:s'),
            'estado'                => $estadoActual,
            'novedad'               => trim($datos['novedad']),
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Novedad registrada.',
            'data'    => $seguimiento,
        ], 201);
    }

    /**
     * Finaliza un viaje.
     */
    public function finalizar(Request $request, Response $response): Response
    {
        $datos = $request->getParsedBody();

        if (empty($datos['programacion_viaje_id'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Se requiere el ID de programación.',
            ], 400);
        }

        $programacionId = (int) $datos['programacion_viaje_id'];
        $estadoActual   = SeguimientoViaje::ultimoEstado($programacionId);

        if (!in_array($estadoActual, ['en_transito', 'retrasado'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Solo se puede finalizar un viaje en tránsito o retrasado.',
            ], 422);
        }

        $seguimiento = SeguimientoViaje::create([
            'programacion_viaje_id' => $programacionId,
            'fecha'                 => date('Y-m-d'),
            'hora'                  => date('H:i:s'),
            'estado'                => 'finalizado',
            'novedad'               => $datos['novedad'] ?? 'Viaje finalizado correctamente.',
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Viaje finalizado.',
            'data'    => $seguimiento,
        ], 201);
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