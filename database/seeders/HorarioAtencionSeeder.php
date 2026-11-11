<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Horario general (departamento_id NULL): lunes a viernes, 14:00 a 15:20.
 */
class HorarioAtencionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horarios = [];

        foreach (range(1, 5) as $diaSemana) {
            $horarios[] = [
                'departamento_id' => null,
                'dia_semana' => $diaSemana,
                'hora_inicio' => '14:00',
                'hora_fin' => '15:20',
                'vigente_desde' => '2026-01-01',
            ];
        }

        DB::table('horario_atencion')->insert($horarios);
    }
}
