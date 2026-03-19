<?php

declare(strict_types=1);

namespace App\Security\Application\User\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterUserRequest implements Command
{
    public function __construct(public string $username = '', #[\SensitiveParameter] public string $password = '', public ?AccountId $accountId = null) {}
}
