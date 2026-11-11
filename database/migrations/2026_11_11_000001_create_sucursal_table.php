<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de sucursales, usado por departamento, oferta_laboral y bloqueo_agenda.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sucursal', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('ciudad', 60);
            $table->boolean('atiende_citas')->default(false);
            $table->boolean('activo')->default(true);

            $table->unique('ciudad', 'uq_sucursal_ciudad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursal');
    }
};
