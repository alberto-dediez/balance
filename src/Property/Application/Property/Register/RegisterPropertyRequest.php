<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Register;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterPropertyRequest implements Command
{
    public function __construct(public string $name = '', public ?AccountId $accountId = null, public ?PropertyInfo $info = null, public ?ExternalId $externalId = null, public ?CompanyId $companyId = null, public ?int $numberOfEmployees = null, public ?int $dailyClients = null, public OperatingRegime $operatingRegime = OperatingRegime::Owned) {}
}
