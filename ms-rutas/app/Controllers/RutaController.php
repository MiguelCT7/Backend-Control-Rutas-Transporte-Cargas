<?php

namespace App\Controllers;

use App\Models\Ruta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RutaController
{
    /**
     * Lista todas las rutas con filtros opcionales.
     */
    public function listar(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query  = Ruta::query();

        if (!empty($params['ciudad'])) {
            $query->where(function ($q) use ($params) {
                $q->where('ciudad_origen', 'like', '%' . $params['ciudad'] . '%')
                  ->orWhere('ciudad_destino', 'like', '%' . $params['ciudad'] . '%');
            });
        }

        $rutas = $query->orderBy('created_at', 'desc')->get();

        return $this->responder($response, [
            'success' => true,
            'data'    => $rutas,
        ]);
    }

    /**
     * Obtiene una ruta por su ID.
     */
    public function obtener(Request $request, Response $response, array $args): Response
    {
        $ruta = Ruta::find((int) $args['id']);

        if (!$ruta) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Ruta no encontrada.',
            ], 404);
        }

        return $this->responder($response, [
            'success' => true,
            'data'    => $ruta,
        ]);
    }

    /**
     * Registra una nueva ruta.
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

        if (Ruta::rutaExiste(trim($datos['ciudad_origen']), trim($datos['ciudad_destino']))) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Ya existe una ruta entre estas ciudades.',
            ], 409);
        }

        $ruta = Ruta::create([
            'ciudad_origen'   => trim($datos['ciudad_origen']),
            'ciudad_destino'  => trim($datos['ciudad_destino']),
            'distancia'       => (float) $datos['distancia'],
            'tiempo_estimado' => trim($datos['tiempo_estimado']),
            'observaciones'   => $datos['observaciones'] ?? null,
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Ruta registrada correctamente.',
            'data'    => $ruta,
        ], 201);
    }

    /**
     * Actualiza una ruta existente.
     */
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        $ruta = Ruta::find((int) $args['id']);

        if (!$ruta) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Ruta no encontrada.',
            ], 404);
        }

        $datos  = $request->getParsedBody();
        $origen  = trim($datos['ciudad_origen'] ?? $ruta->ciudad_origen);
        $destino = trim($datos['ciudad_destino'] ?? $ruta->ciudad_destino);

        if (Ruta::rutaExiste($origen, $destino, $ruta->id)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Ya existe una ruta entre estas ciudades.',
            ], 409);
        }

        if (isset($datos['ciudad_origen']))   $ruta->ciudad_origen   = trim($datos['ciudad_origen']);
        if (isset($datos['ciudad_destino']))  $ruta->ciudad_destino  = trim($datos['ciudad_destino']);
        if (isset($datos['distancia']))       $ruta->distancia       = (float) $datos['distancia'];
        if (isset($datos['tiempo_estimado'])) $ruta->tiempo_estimado = trim($datos['tiempo_estimado']);
        if (array_key_exists('observaciones', $datos)) $ruta->observaciones = $datos['observaciones'];

        $ruta->save();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Ruta actualizada correctamente.',
            'data'    => $ruta,
        ]);
    }

    /**
     * Elimina una ruta.
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $ruta = Ruta::find((int) $args['id']);

        if (!$ruta) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Ruta no encontrada.',
            ], 404);
        }

        $ruta->delete();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Ruta eliminada correctamente.',
        ]);
    }

    /**
     * Valida los campos obligatorios de la ruta.
     */
    private function validarCampos(array $datos): array
    {
        $errores    = [];
        $requeridos = ['ciudad_origen', 'ciudad_destino', 'distancia', 'tiempo_estimado'];

        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo '{$campo}' es obligatorio.";
            }
        }

        if (isset($datos['distancia']) && (float) $datos['distancia'] <= 0) {
            $errores[] = 'La distancia debe ser mayor a cero.';
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