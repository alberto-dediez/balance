<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

class AttendeeSurveyTokenInvalidException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El enlace de la encuesta no es válido.', 404);
    }
}
