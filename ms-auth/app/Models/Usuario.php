<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Usuario
 * Representa la entidad de usuario en la base de datos.
 */
class Usuario extends Model
{
    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'correo',
        'usuario',
        'contrasena',
        'rol',
        'token',
        'sesion_activa',
        'estado',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected $casts = [
        'sesion_activa' => 'boolean',
    ];

    /**
     * Genera un token único para la sesión del usuario.
     */
    public function generarToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Verifica si la contraseña ingresada coincide.
     */
    public function verificarContrasena(string $contrasena): bool
    {
        return $this->contrasena === $contrasena;
    }

    /**
     * Activa la sesión del usuario.
     */
    public function activarSesion(): void
    {
        $this->token = $this->generarToken();
        $this->sesion_activa = true;
        $this->save();
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function cerrarSesion(): void
    {
        $this->token = null;
        $this->sesion_activa = false;
        $this->save();
    }

    /**
     * Busca un usuario por su token de sesión.
     */
    public static function buscarPorToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();
    }
}