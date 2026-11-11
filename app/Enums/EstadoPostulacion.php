<?php

namespace App\Enums;

/**
 * Estado de una postulación (postulacion.estado).
 */
enum EstadoPostulacion: string
{
    case Recibida = 'RECIBIDA';
    case EnRevision = 'EN_REVISION';
    case Preseleccionada = 'PRESELECCIONADA';
    case Descartada = 'DESCARTADA';
    case Contratada = 'CONTRATADA';
}
