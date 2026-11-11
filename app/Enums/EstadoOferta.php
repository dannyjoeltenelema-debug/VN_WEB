<?php

namespace App\Enums;

/**
 * Estado de una oferta laboral (oferta_laboral.estado).
 */
enum EstadoOferta: string
{
    case Borrador = 'BORRADOR';
    case Publicada = 'PUBLICADA';
    case Cerrada = 'CERRADA';
}
