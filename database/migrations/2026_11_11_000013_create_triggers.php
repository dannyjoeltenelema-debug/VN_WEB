<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Triggers con reglas de negocio que la base debe cumplir siempre:
 * - tg_usuario_bi / tg_usuario_bu: un rol que gestiona citas sin acceso total exige departamento.
 * - tg_cita_bi: cupo por turno, una cita pendiente por departamento y sin choques de horario.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
            CREATE TRIGGER tg_usuario_bi BEFORE INSERT ON usuario FOR EACH ROW
            BEGIN
                IF NEW.departamento_id IS NULL AND EXISTS (
                    SELECT 1 FROM rol WHERE id = NEW.rol_id AND gestiona_citas = 1 AND acceso_total = 0) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este rol requiere un departamento asignado';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER tg_usuario_bu BEFORE UPDATE ON usuario FOR EACH ROW
            BEGIN
                IF NEW.departamento_id IS NULL AND EXISTS (
                    SELECT 1 FROM rol WHERE id = NEW.rol_id AND gestiona_citas = 1 AND acceso_total = 0) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este rol requiere un departamento asignado';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER tg_cita_bi BEFORE INSERT ON cita FOR EACH ROW
            BEGIN
                DECLARE v_cupo TINYINT UNSIGNED;
                DECLARE v_ocupados INT;

                -- Bloquea la fila del departamento: dos reservas simultáneas se atienden en fila
                SELECT cupo_por_turno INTO v_cupo
                  FROM departamento WHERE id = NEW.departamento_id FOR UPDATE;

                SELECT COUNT(*) INTO v_ocupados
                  FROM cita
                 WHERE departamento_id = NEW.departamento_id
                   AND fecha = NEW.fecha AND hora_inicio = NEW.hora_inicio
                   AND estado = 'PENDIENTE';
                IF v_ocupados >= v_cupo THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El turno seleccionado ya no tiene cupo';
                END IF;

                IF EXISTS (SELECT 1 FROM cita
                            WHERE identificacion = NEW.identificacion
                              AND departamento_id = NEW.departamento_id
                              AND estado = 'PENDIENTE' AND fecha >= CURDATE()) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya tiene una cita pendiente con este departamento';
                END IF;

                IF EXISTS (SELECT 1 FROM cita
                            WHERE identificacion = NEW.identificacion
                              AND fecha = NEW.fecha
                              AND hora_inicio < NEW.hora_fin AND hora_fin > NEW.hora_inicio
                              AND estado = 'PENDIENTE') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya tiene otra cita en ese mismo horario';
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tg_cita_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS tg_usuario_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS tg_usuario_bi');
    }
};
