<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Departamento de ejemplo en Quito. Completar teléfonos y correos reales y agregar los demás.
 */
class DepartamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sucursalQuitoId = DB::table('sucursal')->where('ciudad', 'Quito')->value('id');

        DB::table('departamento')->insert([
            'sucursal_id' => $sucursalQuitoId,
            'nombre' => 'Inspección',
            'telefono' => '0000000000',
            'email' => 'inspeccion.quito@ejemplo.edu.ec',
        ]);
    }
}
