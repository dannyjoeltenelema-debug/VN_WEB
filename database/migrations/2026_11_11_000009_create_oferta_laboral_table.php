<?php

use App\Enums\EstadoOferta;
use App\Enums\Jornada;
use App\Enums\NivelInstruccion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ofertas laborales de "Trabaja con nosotros" (estructura basada en Encuentra Empleo).
 * sucursal_id NULL = ambas sucursales; sueldo NULL = "A convenir".
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oferta_laboral', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('sucursal_id')->nullable();
            $table->string('titulo', 150);
            $table->text('actividades');
            $table->enum('nivel_instruccion', array_column(NivelInstruccion::cases(), 'value'));
            $table->string('area_estudio', 100)->nullable();
            $table->unsignedTinyInteger('experiencia_min_anios')->default(0);
            $table->string('area_experiencia', 100)->nullable();
            $table->enum('jornada', array_column(Jornada::cases(), 'value'));
            $table->decimal('sueldo', 8, 2)->nullable();
            $table->unsignedTinyInteger('vacantes')->default(1);
            $table->text('informacion_adicional')->nullable();
            $table->date('fecha_publicacion')->nullable();
            $table->date('fecha_cierre');
            $table->decimal('puntaje_minimo', 3, 1)->default(7.0);
            $table->enum('estado', array_column(EstadoOferta::cases(), 'value'))
                ->default(EstadoOferta::Borrador->value);
            $table->unsignedInteger('publicado_por');
            $table->dateTime('creado_en')->useCurrent();
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index(['estado', 'fecha_cierre'], 'ix_oferta_estado');
            $table->foreign('sucursal_id', 'fk_oferta_sucursal')->references('id')->on('sucursal');
            $table->foreign('publicado_por', 'fk_oferta_usuario')->references('id')->on('usuario');
        });

        DB::statement('ALTER TABLE oferta_laboral
            ADD CONSTRAINT ck_oferta_sueldo   CHECK (sueldo IS NULL OR sueldo > 0),
            ADD CONSTRAINT ck_oferta_vacantes CHECK (vacantes >= 1),
            ADD CONSTRAINT ck_oferta_puntaje  CHECK (puntaje_minimo BETWEEN 0 AND 10),
            ADD CONSTRAINT ck_oferta_fechas   CHECK (fecha_publicacion IS NULL OR fecha_cierre >= fecha_publicacion)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oferta_laboral');
    }
};
