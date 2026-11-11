<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Requisitos adicionales de una oferta ("conocimientos del cargo"), usados para puntuar.
 * importancia: 1 baja, 2 media, 3 alta.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requisito_oferta', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('oferta_laboral_id');
            $table->string('descripcion', 255);
            $table->unsignedTinyInteger('importancia')->default(2);
            $table->boolean('obligatorio')->default(false);
            $table->unsignedTinyInteger('orden')->default(0);

            $table->index(['oferta_laboral_id', 'orden'], 'ix_requisito_oferta');
            $table->foreign('oferta_laboral_id', 'fk_requisito_oferta')
                ->references('id')->on('oferta_laboral')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE requisito_oferta
            ADD CONSTRAINT ck_requisito_importancia CHECK (importancia BETWEEN 1 AND 3)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisito_oferta');
    }
};
