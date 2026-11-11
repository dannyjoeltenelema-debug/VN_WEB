<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles del panel. Un usuario = un rol; los interruptores definen qué secciones puede usar.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('codigo', 30);
            $table->string('nombre', 60);
            $table->boolean('acceso_total')->default(false);
            $table->boolean('gestiona_anuncios')->default(false);
            $table->boolean('gestiona_citas')->default(false);
            $table->boolean('gestiona_empleos')->default(false);
            $table->boolean('activo')->default(true);

            $table->unique('codigo', 'uq_rol_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol');
    }
};
