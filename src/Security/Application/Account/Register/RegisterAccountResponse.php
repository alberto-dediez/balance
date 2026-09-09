<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterAccountResponse implements CommandResponse
{
    public function __construct(
        public AccountId $accountId,
        public string $name,
        public bool $active,
        public bool $consultant = false,
    ) {}
}
