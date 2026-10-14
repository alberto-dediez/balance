<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Address;

class RegisterEmployeeRequest implements Command
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public ?string $phone = null,
        public ?string $street = null,
        public ?string $postalCode = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?AccountId $accountId = null,
        public ?UserId $userId = null,
    ) {}

    public function address(): ?Address
    {
        if (null === $this->street && null === $this->postalCode && null === $this->city && null === $this->country) {
            return null;
        }

        return new Address(
            street: $this->street,
            postalCode: $this->postalCode,
            city: $this->city,
            country: $this->country,
        );
    }
}
