<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operadores del panel. Reemplaza a la tabla `users` de Laravel.
 *
 * Adaptación a Laravel: la contraseña se guarda en `password` (en el script, `password_hash`)
 * y se agrega `remember_token` para la opción "recordarme".
 * departamento_id es obligatorio si el rol gestiona citas sin acceso total (trigger tg_usuario_*).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('rol_id');
            $table->unsignedSmallInteger('departamento_id')->nullable();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 150);
            $table->string('password', 255);
            $table->rememberToken();
            $table->boolean('activo')->default(true);
            $table->dateTime('ultimo_acceso')->nullable();
            $table->dateTime('creado_en')->useCurrent();
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unique('email', 'uq_usuario_email');
            $table->index('departamento_id', 'ix_usuario_departamento');
            $table->foreign('rol_id', 'fk_usuario_rol')->references('id')->on('rol');
            $table->foreign('departamento_id', 'fk_usuario_departamento')->references('id')->on('departamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
