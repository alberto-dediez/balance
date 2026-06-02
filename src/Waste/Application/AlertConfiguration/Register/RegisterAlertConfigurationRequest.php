<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterAlertConfigurationRequest implements Command
{
    /**
     * @param array<string, array{enabled: bool, parameters: array<string, mixed>}> $items
     */
    public function __construct(
        public ?AccountId $accountId = null,
        public array $items = [],
    ) {}
}
