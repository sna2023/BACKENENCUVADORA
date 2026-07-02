<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'correo' => 'admin@gmail.com',
                'nombre' => 'Administrador',
                'clave'  => 'admin1234',
                'rol'    => 'administrador',
            ],
            [
                'correo' => 'mentor@gmail.com',
                'nombre' => 'Carlos Mentor',
                'clave'  => 'mentor1234',
                'rol'    => 'mentor',
            ],
            [
                'correo' => 'emprendedor@gmail.com',
                'nombre' => 'Maria Emprendedora',
                'clave'  => 'estudiante1234',
                'rol'    => 'emprendedor',
            ],
        ];

        foreach ($usuarios as $u) {
            $user = User::where('correo', $u['correo'])->first();
            if ($user) {
                $user->update([
                    'clave'         => Hash::make($u['clave']),
                    'clave_visible' => $u['clave'],
                ]);
            } else {
                User::create([
                    'correo'         => $u['correo'],
                    'nombre'         => $u['nombre'],
                    'clave'          => Hash::make($u['clave']),
                    'clave_visible'  => $u['clave'],
                    'rol'            => $u['rol'],
                    'estado'         => 'activo',
                    'fecha_registro' => now(),
                ]);
            }
        }
    }
}
