<?php

declare(strict_types=1);

namespace App\Security\Application\Account\Update;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class UpdateAccountRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public string $name,
        public bool $active,
        public bool $consultant = false,
        public ?AccountId $consultantAccountId = null,
    ) {}
}
