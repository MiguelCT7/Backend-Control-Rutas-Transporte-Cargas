<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = [
        'placa',
        'tipo_vehiculo',
        'capacidad_carga',
        'modelo',
        'marca',
        'estado',
    ];

    protected $casts = [
        'capacidad_carga' => 'float',
    ];

    /**
     * Verifica si la placa ya está registrada.
     */
    public static function placaExiste(string $placa, ?int $excluirId = null): bool
    {
        $query = self::where('placa', $placa);

        if ($excluirId !== null) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->exists();
    }
}