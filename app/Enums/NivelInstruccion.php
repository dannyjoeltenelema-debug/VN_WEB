<?php

namespace App\Enums;

/**
 * Nivel de instrucción (oferta_laboral y postulacion).
 */
enum NivelInstruccion: string
{
    case Bachiller = 'BACHILLER';
    case TecnicoTecnologico = 'TECNICO_TECNOLOGICO';
    case TercerNivel = 'TERCER_NIVEL';
    case CuartoNivel = 'CUARTO_NIVEL';
    case Doctorado = 'DOCTORADO';
}
