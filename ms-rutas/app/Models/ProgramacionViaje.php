<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramacionViaje extends Model
{
    protected $table = 'programaciones_viajes';

    protected $fillable = [
        'conductor_id',
        'vehiculo_id',
        'ruta_id',
        'fecha_salida',
        'hora_salida',
        'fecha_estimada_llegada',
        'observaciones',
        'estado',
    ];

    /**
     * Relación con Ruta.
     */
    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }
}