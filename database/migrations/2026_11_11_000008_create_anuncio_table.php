<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anuncios en imagen PNG con vigencia. Los vencidos se eliminan con una tarea programada.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anuncio', function (Blueprint $table) {
            $table->increments('id');
            $table->string('titulo', 150);
            $table->string('imagen_ruta', 255);
            $table->string('enlace_url', 255)->nullable();
            $table->boolean('mostrar_en_carrusel')->default(false);
            $table->unsignedTinyInteger('orden')->default(0);
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->unsignedInteger('publicado_por');
            $table->dateTime('creado_en')->useCurrent();

            $table->index(['fecha_inicio', 'fecha_fin'], 'ix_anuncio_vigencia');
            $table->foreign('publicado_por', 'fk_anuncio_usuario')->references('id')->on('usuario');
        });

        DB::statement('ALTER TABLE anuncio
            ADD CONSTRAINT ck_anuncio_fechas CHECK (fecha_fin > fecha_inicio)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anuncio');
    }
};
