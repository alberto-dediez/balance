<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterProtocolRequest implements Command
{
    public function __construct(
        public string $name = '',
        public string $description = '',
        public ?AccountId $accountId = null,
        public string $temporaryFilePath = '',
        public string $fileExtension = '',
    ) {}
}
