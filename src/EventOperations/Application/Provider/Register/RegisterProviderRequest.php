<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterProviderRequest implements Command
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
