<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;
use App\Waste\Application\AlertConfiguration\AlertConfigurationItemDto;

class RegisterAlertConfigurationRequest implements Command
{
    /**
     * @param list<AlertConfigurationItemDto> $items
     */
    public function __construct(
        public ?AccountId $accountId = null,
        public array $items = [],
    ) {}
}
