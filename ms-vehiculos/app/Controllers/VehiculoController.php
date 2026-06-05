<?php

namespace App\Controllers;

use App\Models\Vehiculo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VehiculoController
{
    /**
     * Lista todos los vehículos con filtros opcionales.
     */
    public function listar(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query  = Vehiculo::query();

        if (!empty($params['placa'])) {
            $query->where('placa', 'like', '%' . $params['placa'] . '%');
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        if (!empty($params['tipo'])) {
            $query->where('tipo_vehiculo', 'like', '%' . $params['tipo'] . '%');
        }

        $vehiculos = $query->orderBy('created_at', 'desc')->get();

        return $this->responder($response, [
            'success' => true,
            'data'    => $vehiculos,
        ]);
    }

    /**
     * Obtiene un vehículo por su ID.
     */
    public function obtener(Request $request, Response $response, array $args): Response
    {
        $vehiculo = Vehiculo::find((int) $args['id']);

        if (!$vehiculo) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Vehículo no encontrado.',
            ], 404);
        }

        return $this->responder($response, [
            'success' => true,
            'data'    => $vehiculo,
        ]);
    }

    /**
     * Registra un nuevo vehículo.
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

        if (Vehiculo::placaExiste(strtoupper(trim($datos['placa'])))) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'La placa ya está registrada.',
            ], 409);
        }

        $vehiculo = Vehiculo::create([
            'placa'           => strtoupper(trim($datos['placa'])),
            'tipo_vehiculo'   => trim($datos['tipo_vehiculo']),
            'capacidad_carga' => (float) $datos['capacidad_carga'],
            'modelo'          => trim($datos['modelo']),
            'marca'           => trim($datos['marca']),
            'estado'          => $datos['estado'] ?? 'disponible',
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Vehículo registrado correctamente.',
            'data'    => $vehiculo,
        ], 201);
    }

    /**
     * Actualiza la información de un vehículo.
     */
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        $vehiculo = Vehiculo::find((int) $args['id']);

        if (!$vehiculo) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Vehículo no encontrado.',
            ], 404);
        }

        $datos = $request->getParsedBody();

        if (!empty($datos['placa']) && Vehiculo::placaExiste(strtoupper($datos['placa']), $vehiculo->id)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'La placa ya pertenece a otro vehículo.',
            ], 409);
        }

        if (isset($datos['placa']))           $vehiculo->placa           = strtoupper(trim($datos['placa']));
        if (isset($datos['tipo_vehiculo']))   $vehiculo->tipo_vehiculo   = trim($datos['tipo_vehiculo']);
        if (isset($datos['capacidad_carga'])) $vehiculo->capacidad_carga = (float) $datos['capacidad_carga'];
        if (isset($datos['modelo']))          $vehiculo->modelo          = trim($datos['modelo']);
        if (isset($datos['marca']))           $vehiculo->marca           = trim($datos['marca']);
        if (isset($datos['estado']))          $vehiculo->estado          = $datos['estado'];

        $vehiculo->save();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Vehículo actualizado correctamente.',
            'data'    => $vehiculo,
        ]);
    }

    /**
     * Cambia el estado de un vehículo.
     */
    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $vehiculo = Vehiculo::find((int) $args['id']);

        if (!$vehiculo) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Vehículo no encontrado.',
            ], 404);
        }

        $datos   = $request->getParsedBody();
        $estados = ['disponible', 'en_ruta', 'mantenimiento', 'inactivo'];

        if (empty($datos['estado']) || !in_array($datos['estado'], $estados)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Estado inválido. Permitidos: ' . implode(', ', $estados),
            ], 422);
        }

        $vehiculo->estado = $datos['estado'];
        $vehiculo->save();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Estado del vehículo actualizado.',
            'data'    => $vehiculo,
        ]);
    }

    /**
     * Elimina un vehículo.
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $vehiculo = Vehiculo::find((int) $args['id']);

        if (!$vehiculo) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Vehículo no encontrado.',
            ], 404);
        }

        $vehiculo->delete();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Vehículo eliminado correctamente.',
        ]);
    }

    /**
     * Valida los campos obligatorios del vehículo.
     */
    private function validarCampos(array $datos): array
    {
        $errores    = [];
        $requeridos = ['placa', 'tipo_vehiculo', 'capacidad_carga', 'modelo', 'marca'];

        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo '{$campo}' es obligatorio.";
            }
        }

        if (isset($datos['capacidad_carga']) && (float) $datos['capacidad_carga'] <= 0) {
            $errores[] = 'La capacidad de carga debe ser mayor a cero.';
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