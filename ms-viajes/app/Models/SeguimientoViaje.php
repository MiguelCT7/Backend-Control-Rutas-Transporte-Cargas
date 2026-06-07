<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoViaje extends Model
{
    protected $table = 'seguimientos_viajes';

    protected $fillable = [
        'programacion_viaje_id',
        'fecha',
        'hora',
        'estado',
        'novedad',
    ];

    /**
     * Obtiene el último estado registrado para una programación.
     */
    public static function ultimoEstado(int $programacionId): ?string
    {
        $ultimo = self::where('programacion_viaje_id', $programacionId)
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->first();

        return $ultimo?->estado;
    }

    /**
     * Obtiene el historial completo de un viaje.
     */
    public static function historial(int $programacionId)
    {
        return self::where('programacion_viaje_id', $programacionId)
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get();
    }
}