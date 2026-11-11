<?php

namespace App\Enums;

/**
 * Tipo de documento de identidad (cita y postulacion).
 */
enum TipoIdentificacion: string
{
    case Cedula = 'CEDULA';
    case Pasaporte = 'PASAPORTE';
}
