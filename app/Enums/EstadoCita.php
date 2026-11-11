<?php

namespace App\Enums;

/**
 * Estado de una cita (cita.estado).
 */
enum EstadoCita: string
{
    case Pendiente = 'PENDIENTE';
    case Atendida = 'ATENDIDA';
    case NoAsistio = 'NO_ASISTIO';
    case Cancelada = 'CANCELADA';
}
