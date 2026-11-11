<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Departamentos que atienden citas. Cada uno pertenece a una sucursal.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departamento', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedTinyInteger('sucursal_id');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('telefono', 20);
            $table->string('extension', 10)->nullable();
            $table->string('email', 150);
            $table->unsignedTinyInteger('duracion_cita_min')->default(15);
            $table->unsignedTinyInteger('cupo_por_turno')->default(2);
            $table->unsignedTinyInteger('dias_max_anticipacion')->default(14);
            $table->boolean('activo')->default(true);
            $table->dateTime('creado_en')->useCurrent();
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['sucursal_id', 'nombre'], 'uq_departamento_sucursal_nombre');
            $table->foreign('sucursal_id', 'fk_departamento_sucursal')->references('id')->on('sucursal');
        });

        DB::statement('ALTER TABLE departamento
            ADD CONSTRAINT ck_departamento_duracion CHECK (duracion_cita_min BETWEEN 5 AND 120),
            ADD CONSTRAINT ck_departamento_cupo     CHECK (cupo_por_turno BETWEEN 1 AND 10),
            ADD CONSTRAINT ck_departamento_dias     CHECK (dias_max_anticipacion BETWEEN 0 AND 90)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departamento');
    }
};
