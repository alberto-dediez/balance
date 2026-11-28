<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

class ContestEntryCountryNotAllowedException extends \RuntimeException
{
    public function __construct(string $country)
    {
        parent::__construct(sprintf('El país %s no puede participar en este concurso.', $country), 409);
    }
}
