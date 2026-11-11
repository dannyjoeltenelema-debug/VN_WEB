<?php

use App\Enums\EstadoCita;
use App\Enums\TipoIdentificacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Citas reservadas por el público (sin login). La persona no puede cancelar.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cita', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo', 20);
            $table->unsignedSmallInteger('departamento_id');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->enum('tipo_identificacion', array_column(TipoIdentificacion::cases(), 'value'))
                ->default(TipoIdentificacion::Cedula->value);
            $table->string('identificacion', 20);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 150);
            $table->string('telefono', 20);
            $table->string('motivo', 500);
            $table->boolean('acepta_tratamiento_datos');
            $table->enum('estado', array_column(EstadoCita::cases(), 'value'))
                ->default(EstadoCita::Pendiente->value);
            $table->unsignedInteger('gestionado_por')->nullable();
            $table->string('observacion_interna', 500)->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->dateTime('creado_en')->useCurrent();
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unique('codigo', 'uq_cita_codigo');
            $table->index(['departamento_id', 'fecha', 'hora_inicio', 'estado'], 'ix_cita_agenda');
            $table->index(['identificacion', 'estado', 'fecha'], 'ix_cita_persona');
            $table->foreign('departamento_id', 'fk_cita_departamento')->references('id')->on('departamento');
            $table->foreign('gestionado_por', 'fk_cita_usuario')->references('id')->on('usuario')->nullOnDelete();
        });

        DB::statement('ALTER TABLE cita
            ADD CONSTRAINT ck_cita_horas CHECK (hora_fin > hora_inicio),
            ADD CONSTRAINT ck_cita_datos CHECK (acepta_tratamiento_datos = 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cita');
    }
};
