<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $table = 'rutas';

    protected $fillable = [
        'ciudad_origen',
        'ciudad_destino',
        'distancia',
        'tiempo_estimado',
        'observaciones',
    ];

    protected $casts = [
        'distancia' => 'float',
    ];

    /**
     * Verifica si ya existe una ruta entre las mismas ciudades.
     */
    public static function rutaExiste(string $origen, string $destino, ?int $excluirId = null): bool
    {
        $query = self::where('ciudad_origen', $origen)
            ->where('ciudad_destino', $destino);

        if ($excluirId !== null) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->exists();
    }
}