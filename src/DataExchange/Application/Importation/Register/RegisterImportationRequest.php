<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterImportationRequest implements Command
{
    public function __construct(
        public string $name,
        public string $type,
        public string $path,
        public int $offset,
        public string $extension,
        public ?AccountId $accountId,
    ) {}
}
