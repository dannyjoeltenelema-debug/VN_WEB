<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * El primer Super Admin no se siembra: se crea desde la aplicación para que la
     * contraseña se guarde con hash y nunca en texto plano.
     */
    public function run(): void
    {
        $this->call([
            SucursalSeeder::class,
            RolSeeder::class,
            HorarioAtencionSeeder::class,
            DepartamentoSeeder::class,
        ]);
    }
}
