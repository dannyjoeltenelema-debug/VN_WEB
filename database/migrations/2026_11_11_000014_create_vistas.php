<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vistas de apoyo:
 * - v_anuncio_vigente: anuncios visibles hoy.
 * - v_oferta_abierta: ofertas visibles en "Trabaja con nosotros".
 * - v_postulacion_puntaje: lista de RR.HH. con el puntaje más reciente de cada postulación.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            CREATE OR REPLACE VIEW v_anuncio_vigente AS
            SELECT id, titulo, imagen_ruta, enlace_url, mostrar_en_carrusel, orden, fecha_inicio, fecha_fin
              FROM anuncio
             WHERE NOW() BETWEEN fecha_inicio AND fecha_fin
        ');

        DB::statement("
            CREATE OR REPLACE VIEW v_oferta_abierta AS
            SELECT o.id, o.titulo, o.actividades, o.nivel_instruccion, o.area_estudio,
                   o.experiencia_min_anios, o.area_experiencia, o.jornada,
                   o.sueldo,
                   o.vacantes, o.informacion_adicional, o.fecha_publicacion, o.fecha_cierre,
                   IFNULL(s.ciudad, 'Todas las sucursales') AS lugar_trabajo
              FROM oferta_laboral o
              LEFT JOIN sucursal s ON s.id = o.sucursal_id
             WHERE o.estado = 'PUBLICADA'
               AND (o.fecha_publicacion IS NULL OR o.fecha_publicacion <= CURDATE())
               AND o.fecha_cierre >= CURDATE()
        ");

        DB::statement("
            CREATE OR REPLACE VIEW v_postulacion_puntaje AS
            SELECT p.id, p.oferta_laboral_id, o.titulo AS oferta,
                   p.nombres, p.apellidos, p.email, p.telefono, p.estado, p.creado_en,
                   e.puntaje, e.metodo, e.justificacion,
                   CASE WHEN e.puntaje IS NULL THEN 'SIN_EVALUAR'
                        WHEN e.puntaje >= o.puntaje_minimo THEN 'CONSIDERADA'
                        ELSE 'BAJO_MINIMO' END AS clasificacion
              FROM postulacion p
              JOIN oferta_laboral o ON o.id = p.oferta_laboral_id
              LEFT JOIN (
                    SELECT postulacion_id, puntaje, metodo, justificacion,
                           ROW_NUMBER() OVER (PARTITION BY postulacion_id ORDER BY creado_en DESC, id DESC) AS n
                      FROM evaluacion_postulacion
              ) e ON e.postulacion_id = p.id AND e.n = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_postulacion_puntaje');
        DB::statement('DROP VIEW IF EXISTS v_oferta_abierta');
        DB::statement('DROP VIEW IF EXISTS v_anuncio_vigente');
    }
};
