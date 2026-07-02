<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mentor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Lista todos los mentores con conteo de proyectos asignados.
     */
    public function mentors()
    {
        $mentors = User::where('rol', 'mentor')
            ->get()
            ->map(fn($m) => [
                'id'        => $m->id_usuario,
                'nombre'    => $m->nombre,
                'correo'    => $m->correo,
            ]);

        return response()->json($mentors);
    }

    /**
     * Crea un nuevo mentor (solo admin).
     */
    public function createMentor(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo',
            'clave'  => 'required|string|min:8',
        ]);

        $mentor = User::create([
            'nombre'         => $validated['nombre'],
            'correo'         => $validated['correo'],
            'clave'          => Hash::make($validated['clave']),
            'rol'            => 'mentor',
            'estado'         => 'activo',
            'fecha_registro' => now(),
        ]);

        return response()->json([
            'message' => 'Mentor creado exitosamente',
            'data'    => $mentor,
        ], 201);
    }

    /**
     * Elimina un mentor (solo admin).
     */
    public function deleteMentor(Request $request, User $mentor)
    {
        $this->authorizeAdmin($request);

        if ($mentor->rol !== 'mentor') {
            return response()->json(['message' => 'El usuario no es un mentor'], 400);
        }

        $mentor->delete();

        return response()->json(['message' => 'Mentor eliminado']);
    }

    /**
     * Lista todos los usuarios del sistema.
     */
    /**
     * Lista solo los mentores activos (para selectores de asignación).
     */
    public function mentoresActivos(Request $request)
    {
        $this->authorizeAdmin($request);

        $mentores = User::where('rol', 'mentor')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'correo', 'rol', 'estado']);

        return response()->json($mentores);
    }

    public function usuarios(Request $request)
    {
        $this->authorizeAdmin($request);

        $usuarios = User::orderBy('id_usuario')
            ->get(['id_usuario', 'nombre', 'correo', 'clave_visible', 'rol', 'estado', 'fecha_registro']);

        return response()->json($usuarios);
    }

    /**
     * Crea un nuevo usuario.
     */
    public function crearUsuario(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150|unique:usuarios,correo',
            'rol'    => 'required|in:administrador,mentor,emprendedor',
            'clave'  => 'required|string|min:8',
        ], [
            'correo.unique'   => 'Este correo ya está registrado.',
            'clave.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $usuario = User::create([
            'nombre'         => $validated['nombre'],
            'correo'         => $validated['correo'],
            'clave'          => Hash::make($validated['clave']),
            'clave_visible'  => $validated['clave'],
            'rol'            => $validated['rol'],
            'estado'         => 'activo',
            'fecha_registro' => now(),
        ]);

        if ($validated['rol'] === 'mentor') {
            Mentor::create(['id_usuario' => $usuario->id_usuario]);
        }

        return response()->json([
            'message' => 'Usuario creado exitosamente.',
            'data'    => $usuario->only(['id_usuario', 'nombre', 'correo', 'clave_visible', 'rol', 'estado', 'fecha_registro']),
        ], 201);
    }

    /**
     * Edita nombre, correo, rol y opcionalmente la clave de un usuario.
     */
    public function editarUsuario(Request $request, User $usuario)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => 'required|email|max:150|unique:usuarios,correo,' . $usuario->id_usuario . ',id_usuario',
            'rol'    => 'required|in:administrador,mentor,emprendedor',
            'clave'  => 'nullable|string|min:8',
        ], [
            'correo.unique' => 'Este correo ya está registrado por otro usuario.',
            'clave.min'     => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $rolAnterior = $usuario->rol;

        $usuario->nombre = $validated['nombre'];
        $usuario->correo = $validated['correo'];
        $usuario->rol    = $validated['rol'];
        if (!empty($validated['clave'])) {
            $usuario->clave = Hash::make($validated['clave']);
            $usuario->clave_visible = $validated['clave'];
        }
        $usuario->save();

        if ($validated['rol'] === 'mentor' && $rolAnterior !== 'mentor') {
            Mentor::firstOrCreate(['id_usuario' => $usuario->id_usuario]);
        } elseif ($validated['rol'] !== 'mentor' && $rolAnterior === 'mentor') {
            Mentor::where('id_usuario', $usuario->id_usuario)->delete();
        }

        return response()->json([
            'message' => 'Usuario actualizado.',
            'data'    => $usuario->only(['id_usuario', 'nombre', 'correo', 'clave_visible', 'rol', 'estado']),
        ]);
    }

    /**
     * Elimina un usuario.
     */
    public function eliminarUsuario(Request $request, User $usuario)
    {
        $this->authorizeAdmin($request);

        abort_if(
            $usuario->id_usuario === $request->user()->id_usuario,
            400,
            'No puedes eliminar tu propia cuenta.'
        );

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function toggleEstado(Request $request, User $usuario)
    {
        $this->authorizeAdmin($request);

        $usuario->estado = $usuario->estado === 'activo' ? 'inactivo' : 'activo';
        $usuario->save();

        return response()->json([
            'message' => 'Estado actualizado',
            'estado'  => $usuario->estado,
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->rol === 'administrador', 403, 'Solo administradores pueden realizar esta acción.');
    }
}
