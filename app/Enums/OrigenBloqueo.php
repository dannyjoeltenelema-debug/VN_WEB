<?php

namespace App\Enums;

/**
 * Origen de un bloqueo de agenda (bloqueo_agenda.origen).
 */
enum OrigenBloqueo: string
{
    case Api = 'API';
    case Manual = 'MANUAL';
}
