<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\Property\Domain\Model\Company\CompanyId;

class CompanyCarbonFootprintAlreadyRegisteredException extends \InvalidArgumentException
{
    public function __construct(CompanyId $companyId, int $year)
    {
        parent::__construct(sprintf('A carbon footprint already exists for company "%s" and year "%d".', $companyId, $year));
    }
}
