<?php

use App\Enums\EstadoPostulacion;
use App\Enums\NivelInstruccion;
use App\Enums\TipoIdentificacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Postulaciones públicas (sin login). CV solo PDF, máximo 5 MB, guardado en disco privado.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('postulacion', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('oferta_laboral_id');
            $table->enum('tipo_identificacion', array_column(TipoIdentificacion::cases(), 'value'))
                ->default(TipoIdentificacion::Cedula->value);
            $table->string('identificacion', 20);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 150);
            $table->string('telefono', 20);
            $table->string('ciudad_residencia', 80);
            $table->enum('nivel_instruccion', array_column(NivelInstruccion::cases(), 'value'));
            $table->string('titulo_obtenido', 150)->nullable();
            $table->unsignedTinyInteger('anios_experiencia')->default(0);
            $table->string('cv_ruta', 255);
            $table->string('cv_nombre_original', 255);
            $table->unsignedInteger('cv_tamano_bytes');
            $table->boolean('acepta_tratamiento_datos');
            $table->enum('estado', array_column(EstadoPostulacion::cases(), 'value'))
                ->default(EstadoPostulacion::Recibida->value);
            $table->unsignedInteger('revisado_por')->nullable();
            $table->string('ip_origen', 45)->nullable();
            $table->dateTime('creado_en')->useCurrent();
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['oferta_laboral_id', 'identificacion'], 'uq_postulacion_persona_oferta');
            $table->index(['oferta_laboral_id', 'estado'], 'ix_postulacion_estado');
            $table->foreign('oferta_laboral_id', 'fk_postulacion_oferta')->references('id')->on('oferta_laboral');
            $table->foreign('revisado_por', 'fk_postulacion_usuario')->references('id')->on('usuario')->nullOnDelete();
        });

        DB::statement("ALTER TABLE postulacion
            ADD CONSTRAINT ck_postulacion_cv    CHECK (cv_tamano_bytes BETWEEN 1 AND 5242880),
            ADD CONSTRAINT ck_postulacion_pdf   CHECK (LOWER(cv_nombre_original) LIKE '%.pdf'),
            ADD CONSTRAINT ck_postulacion_datos CHECK (acepta_tratamiento_datos = 1)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulacion');
    }
};
