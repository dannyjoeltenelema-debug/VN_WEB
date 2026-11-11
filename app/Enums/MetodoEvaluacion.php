<?php

namespace App\Enums;

/**
 * Método con que se evaluó una postulación (evaluacion_postulacion.metodo).
 */
enum MetodoEvaluacion: string
{
    case Reglas = 'REGLAS';
    case Manual = 'MANUAL';
    case Ia = 'IA';
}
