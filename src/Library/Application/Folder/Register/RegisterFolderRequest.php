<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterFolderRequest implements Command
{
    public function __construct(
        public string $name = '',
        public ?AccountId $accountId = null,
    ) {}
}
