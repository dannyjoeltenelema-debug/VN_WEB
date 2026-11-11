<?php

use App\Enums\MetodoEvaluacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puntuaciones (0–10) de una postulación; cuenta la más reciente.
 * Hoy: REGLAS o MANUAL. Futuro: IA, sin cambiar la estructura.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evaluacion_postulacion', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('postulacion_id');
            $table->decimal('puntaje', 3, 1);
            $table->enum('metodo', array_column(MetodoEvaluacion::cases(), 'value'));
            $table->text('justificacion')->nullable();
            $table->json('detalle')->nullable();
            $table->string('proveedor_ia', 50)->nullable();
            $table->string('modelo_ia', 80)->nullable();
            $table->unsignedInteger('evaluado_por')->nullable();
            $table->dateTime('creado_en')->useCurrent();

            $table->index(['postulacion_id', 'creado_en'], 'ix_evaluacion_postulacion');
            $table->foreign('postulacion_id', 'fk_evaluacion_postulacion')
                ->references('id')->on('postulacion')->cascadeOnDelete();
            $table->foreign('evaluado_por', 'fk_evaluacion_usuario')->references('id')->on('usuario')->nullOnDelete();
        });

        DB::statement('ALTER TABLE evaluacion_postulacion
            ADD CONSTRAINT ck_evaluacion_puntaje CHECK (puntaje BETWEEN 0 AND 10)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_postulacion');
    }
};
