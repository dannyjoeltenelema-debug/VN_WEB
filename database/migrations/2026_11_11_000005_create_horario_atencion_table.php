<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Horarios de atención.
 * departamento_id NULL = horario general; con valor = horario propio que reemplaza al general.
 * dia_semana: 1 = lunes … 7 = domingo (WEEKDAY(fecha) + 1).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('horario_atencion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('departamento_id')->nullable();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();

            $table->index(['departamento_id', 'dia_semana', 'vigente_desde'], 'ix_horario_busqueda');
            $table->foreign('departamento_id', 'fk_horario_departamento')
                ->references('id')->on('departamento')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE horario_atencion
            ADD CONSTRAINT ck_horario_dia      CHECK (dia_semana BETWEEN 1 AND 7),
            ADD CONSTRAINT ck_horario_horas    CHECK (hora_fin > hora_inicio),
            ADD CONSTRAINT ck_horario_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_atencion');
    }
};
