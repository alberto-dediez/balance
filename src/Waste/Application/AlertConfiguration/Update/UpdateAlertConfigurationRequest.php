<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Update;

use App\Shared\Application\CommandBus\Command;
use App\Waste\Application\AlertConfiguration\AlertConfigurationItemDto;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;

class UpdateAlertConfigurationRequest implements Command
{
    /**
     * @param list<AlertConfigurationItemDto> $items
     */
    public function __construct(
        public AlertConfigurationId $alertConfigurationId,
        public array $items = [],
    ) {}
}
