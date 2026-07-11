<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

class AttendeeSurveyClosedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('La encuesta de asistentes ya se ha cerrado y no admite más respuestas.', 409);
    }
}
