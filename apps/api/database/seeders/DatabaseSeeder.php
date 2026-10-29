<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesSeeder::class);

        $usuarios = [
            ['name' => 'Administrador de prueba', 'email' => 'admin@aleacionprima.test', 'rol' => Rol::Administrador],
            ['name' => 'Ventas de prueba', 'email' => 'ventas@aleacionprima.test', 'rol' => Rol::Ventas],
            ['name' => 'Encargado de prueba', 'email' => 'encargado@aleacionprima.test', 'rol' => Rol::Encargado],
        ];

        foreach ($usuarios as $datos) {
            $usuario = User::query()->firstOrCreate(
                ['email' => $datos['email']],
                ['name' => $datos['name'], 'password' => 'password'],
            );
            $usuario->syncRoles([$datos['rol']]);
        }

        $this->call(AreaSeeder::class);
    }
}
