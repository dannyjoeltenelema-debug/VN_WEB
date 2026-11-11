<?php

use App\Enums\OrigenBloqueo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feriados y cierres (siempre la tarde completa).
 * sucursal y departamento NULL = feriado nacional; solo sucursal = cierre de esa sucursal;
 * departamento = cierre de ese departamento. Las columnas *_clave evitan duplicados con NULL.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bloqueo_agenda', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('sucursal_id')->nullable();
            $table->unsignedSmallInteger('departamento_id')->nullable();
            $table->date('fecha');
            $table->string('motivo', 150);
            $table->enum('origen', array_column(OrigenBloqueo::cases(), 'value'))
                ->default(OrigenBloqueo::Manual->value);
            $table->unsignedInteger('creado_por')->nullable();
            $table->dateTime('creado_en')->useCurrent();
            $table->unsignedTinyInteger('sucursal_clave')->storedAs('IFNULL(sucursal_id, 0)');
            $table->unsignedSmallInteger('departamento_clave')->storedAs('IFNULL(departamento_id, 0)');

            $table->unique(['fecha', 'sucursal_clave', 'departamento_clave'], 'uq_bloqueo');
            $table->foreign('sucursal_id', 'fk_bloqueo_sucursal')->references('id')->on('sucursal');
            $table->foreign('departamento_id', 'fk_bloqueo_departamento')->references('id')->on('departamento');
            $table->foreign('creado_por', 'fk_bloqueo_usuario')->references('id')->on('usuario')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloqueo_agenda');
    }
};
