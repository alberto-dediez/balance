<?php

declare(strict_types=1);

namespace App\Property\Application\Company\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterCompanyRequest implements Command
{
    public function __construct(
        public string $name = '',
        public string $vatNumber = '',
        public string $street = '',
        public string $postalCode = '',
        public string $city = '',
        public string $country = '',
        public ?AccountId $accountId = null,
    ) {}
}
