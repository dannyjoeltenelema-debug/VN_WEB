<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sucursales de la institución. Hoy solo Quito atiende citas.
 */
class SucursalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sucursal')->insert([
            ['ciudad' => 'Quito', 'atiende_citas' => true],
            ['ciudad' => 'Cojimíes', 'atiende_citas' => false],
        ]);
    }
}
