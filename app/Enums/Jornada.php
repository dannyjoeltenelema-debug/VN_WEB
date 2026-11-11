<?php

namespace App\Enums;

/**
 * Jornada laboral de una oferta (oferta_laboral.jornada).
 */
enum Jornada: string
{
    case TiempoCompleto = 'TIEMPO_COMPLETO';
    case MedioTiempo = 'MEDIO_TIEMPO';
    case PorHoras = 'POR_HORAS';
}
