<?php

declare(strict_types=1);

namespace App\Property\Application\Company\Register;

use App\Property\Domain\Model\Company\CompanyId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCompanyResponse implements CommandResponse
{
    public function __construct(
        public CompanyId $companyId,
        public string $name,
    ) {}
}
