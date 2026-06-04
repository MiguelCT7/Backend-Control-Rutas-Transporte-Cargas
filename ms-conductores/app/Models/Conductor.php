<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Conductor
 * Representa un conductor de la flota de transporte.
 */
class Conductor extends Model
{
    protected $table = 'conductores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'correo',
        'numero_licencia',
        'categoria_licencia',
        'fecha_vencimiento_licencia',
        'estado',
    ];

    protected $casts = [
        'fecha_vencimiento_licencia' => 'date',
    ];

    /**
     * Verifica si el documento ya está registrado.
     */
    public static function documentoExiste(string $documento, ?int $excluirId = null): bool
    {
        $query = self::where('documento', $documento);

        if ($excluirId !== null) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->exists();
    }

    /**
     * Verifica si la licencia ya está registrada.
     */
    public static function licenciaExiste(string $licencia, ?int $excluirId = null): bool
    {
        $query = self::where('numero_licencia', $licencia);

        if ($excluirId !== null) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->exists();
    }
}