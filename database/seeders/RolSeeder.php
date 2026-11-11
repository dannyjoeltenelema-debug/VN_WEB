<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Roles del panel con sus interruptores de permisos.
 */
class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('rol')->insert([
            ['codigo' => 'SUPER_ADMIN', 'nombre' => 'Super Administrador', 'acceso_total' => true, 'gestiona_anuncios' => true, 'gestiona_citas' => true, 'gestiona_empleos' => true],
            ['codigo' => 'RECTORADO', 'nombre' => 'Rectorado', 'acceso_total' => true, 'gestiona_anuncios' => true, 'gestiona_citas' => true, 'gestiona_empleos' => true],
            ['codigo' => 'INSPECCION', 'nombre' => 'Inspección', 'acceso_total' => false, 'gestiona_anuncios' => true, 'gestiona_citas' => true, 'gestiona_empleos' => false],
            ['codigo' => 'RRHH', 'nombre' => 'Recursos Humanos', 'acceso_total' => false, 'gestiona_anuncios' => false, 'gestiona_citas' => false, 'gestiona_empleos' => true],
            ['codigo' => 'DEPARTAMENTO', 'nombre' => 'Departamento', 'acceso_total' => false, 'gestiona_anuncios' => false, 'gestiona_citas' => true, 'gestiona_empleos' => false],
        ]);
    }
}
