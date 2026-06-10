<?php

namespace App\Controllers;

use App\Models\Conductor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controlador de Conductores
 * Gestiona el CRUD completo de conductores.
 */
class ConductorController
{
    /**
     * Lista todos los conductores con filtros opcionales.
     */
    public function listar(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $query  = Conductor::query();

        if (!empty($params['documento'])) {
            $query->where('documento', 'like', '%' . $params['documento'] . '%');
        }

        if (!empty($params['licencia'])) {
            $query->where('numero_licencia', 'like', '%' . $params['licencia'] . '%');
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $conductores = $query->orderBy('created_at', 'desc')->get();

        return $this->responder($response, [
            'success' => true,
            'data'    => $conductores,
        ]);
    }

    /**
     * Obtiene un conductor por su ID.
     */
    public function obtener(Request $request, Response $response, array $args): Response
    {
        $conductor = Conductor::find((int) $args['id']);

        if (!$conductor) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Conductor no encontrado.',
            ], 404);
        }

        return $this->responder($response, [
            'success' => true,
            'data'    => $conductor,
        ]);
    }

    /**
     * Registra un nuevo conductor.
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

        if (Conductor::documentoExiste($datos['documento'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'El número de documento ya está registrado.',
            ], 409);
        }

        if (Conductor::licenciaExiste($datos['numero_licencia'])) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'El número de licencia ya está registrado.',
            ], 409);
        }

        $conductor = Conductor::create([
            'nombres'                    => trim($datos['nombres']),
            'apellidos'                  => trim($datos['apellidos']),
            'documento'                  => trim($datos['documento']),
            'telefono'                   => trim($datos['telefono']),
            'correo'                     => trim($datos['correo']),
            'numero_licencia'            => trim($datos['numero_licencia']),
            'categoria_licencia'         => trim($datos['categoria_licencia']),
            'fecha_vencimiento_licencia' => $datos['fecha_vencimiento_licencia'],
            'estado'                     => $datos['estado'] ?? 'disponible',
        ]);

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Conductor registrado correctamente.',
            'data'    => $conductor,
        ], 201);
    }

    /**
     * Actualiza la información de un conductor.
     */
    public function actualizar(Request $request, Response $response, array $args): Response
    {
        $conductor = Conductor::find((int) $args['id']);

        if (!$conductor) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Conductor no encontrado.',
            ], 404);
        }

        $datos = $request->getParsedBody();

        if (!empty($datos['documento']) && Conductor::documentoExiste($datos['documento'], $conductor->id)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'El número de documento ya pertenece a otro conductor.',
            ], 409);
        }

        if (!empty($datos['numero_licencia']) && Conductor::licenciaExiste($datos['numero_licencia'], $conductor->id)) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'El número de licencia ya pertenece a otro conductor.',
            ], 409);
        }

        $camposPermitidos = [
            'nombres', 'apellidos', 'documento', 'telefono', 'correo',
            'numero_licencia', 'categoria_licencia', 'fecha_vencimiento_licencia', 'estado',
        ];

        foreach ($camposPermitidos as $campo) {
            if (isset($datos[$campo])) {
                $conductor->$campo = is_string($datos[$campo]) ? trim($datos[$campo]) : $datos[$campo];
            }
        }

        $conductor->save();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Conductor actualizado correctamente.',
            'data'    => $conductor,
        ]);
    }

    /**
     * Elimina un conductor.
     */
    public function eliminar(Request $request, Response $response, array $args): Response
    {
        $conductor = Conductor::find((int) $args['id']);

        if (!$conductor) {
            return $this->responder($response, [
                'success' => false,
                'mensaje' => 'Conductor no encontrado.',
            ], 404);
        }

        $conductor->delete();

        return $this->responder($response, [
            'success' => true,
            'mensaje' => 'Conductor eliminado correctamente.',
        ]);
    }

    /**
 * Lista conductores con licencia próxima a vencer en los próximos 30 días.
 */
        public function licenciasPorVencer(Request $request, Response $response): Response
        {
            $params = $request->getQueryParams();
            $dias   = isset($params['dias']) ? (int) $params['dias'] : 30;

            $hoy     = date('Y-m-d');
            $limite  = date('Y-m-d', strtotime("+{$dias} days"));

            $conductores = Conductor::where('fecha_vencimiento_licencia', '>=', $hoy)
                ->where('fecha_vencimiento_licencia', '<=', $limite)
                ->where('estado', '!=', 'inactivo')
                ->orderBy('fecha_vencimiento_licencia', 'asc')
                ->get()
                ->map(function ($conductor) use ($hoy) {
                    $vencimiento  = new \DateTime($conductor->fecha_vencimiento_licencia);
                    $hoyDate      = new \DateTime($hoy);
                    $diasRestantes = (int) $hoyDate->diff($vencimiento)->days;

                    $conductor->dias_restantes = $diasRestantes;
                    $conductor->alerta         = $diasRestantes <= 7 ? 'critica' : 'advertencia';

                    return $conductor;
                });

            return $this->responder($response, [
                'success'        => true,
                'dias_evaluados' => $dias,
                'total'          => $conductores->count(),
                'data'           => $conductores,
            ]);
        }

    /**
     * Valida los campos obligatorios del conductor.
     */
    private function validarCampos(array $datos): array
    {
        $errores    = [];
        $requeridos = [
            'nombres', 'apellidos', 'documento', 'telefono', 'correo',
            'numero_licencia', 'categoria_licencia', 'fecha_vencimiento_licencia',
        ];

        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo '{$campo}' es obligatorio.";
            }
        }

        if (!empty($datos['correo']) && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
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