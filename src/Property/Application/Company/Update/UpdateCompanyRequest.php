<?php

declare(strict_types=1);

namespace App\Property\Application\Company\Update;

use App\Property\Domain\Model\Company\CompanyId;
use App\Shared\Application\CommandBus\Command;

class UpdateCompanyRequest implements Command
{
    public function __construct(
        public CompanyId $companyId,
        public string $name,
        public string $vatNumber,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $country,
    ) {}
}
