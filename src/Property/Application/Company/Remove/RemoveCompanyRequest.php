<?php

declare(strict_types=1);

namespace App\Property\Application\Company\Remove;

use App\Property\Domain\Model\Company\CompanyId;
use App\Shared\Application\CommandBus\Command;

class RemoveCompanyRequest implements Command
{
    public function __construct(public CompanyId $companyId) {}
}
