<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

class EmployeeContributionTokenInvalidException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('El enlace de contribución no es válido.', 404);
    }
}
