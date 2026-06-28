<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nombre' => 'Administrador',
            'correo' => 'admin@uniincubadora.edu.ec',
            'clave'  => Hash::make('password1234'),
            'rol'    => 'administrador',
            'estado' => 'activo',
        ]);
    }
}
