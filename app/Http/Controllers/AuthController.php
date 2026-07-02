<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150|unique:usuarios,correo',
            'rol'    => 'required|in:mentor,emprendedor',
            'clave'  => 'required|string|min:8|confirmed',
        ], [
            'correo.unique'    => 'Este correo ya tiene una cuenta registrada.',
            'correo.email'     => 'Ingresa un correo válido.',
            'rol.in'           => 'El rol debe ser mentor o emprendedor.',
            'nombre.required'  => 'El nombre es obligatorio.',
            'clave.min'        => 'La contraseña debe tener al menos 8 caracteres.',
            'clave.confirmed'  => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'nombre'          => $validated['nombre'],
            'correo'          => $validated['correo'],
            'clave'           => Hash::make($validated['clave']),
            'clave_visible'   => $validated['clave'],
            'rol'             => $validated['rol'],
            'estado'          => 'activo',
            'fecha_registro'  => now(),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        $rolLabel = $user->rol === 'mentor' ? 'Mentor' : 'Emprendedor';
        $fecha    = now()->format('d/m/Y H:i');
        $admins   = User::where('rol', 'administrador')->pluck('id_usuario');
        foreach ($admins as $adminId) {
            Notificacion::create([
                'id_usuario' => $adminId,
                'tipo'       => 'nuevo_usuario',
                'mensaje'    => "Nuevo {$rolLabel} registrado: \"{$user->nombre}\" ({$user->correo}) el {$fecha}.",
                'url'        => '/admin/usuarios',
            ]);
        }

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'clave'  => 'required',
        ], [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email'    => 'Ingresa un correo válido.',
            'clave.required'  => 'La contraseña es obligatoria.',
        ]);

        $user = User::where('correo', $request->correo)
                    ->where('estado', 'activo')
                    ->first();

        if (! $user || ! Hash::check($request->clave, $user->clave)) {
            throw ValidationException::withMessages([
                'correo' => ['Las credenciales no son correctas.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
