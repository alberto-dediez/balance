<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration;

use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use App\Waste\Domain\Model\Waste\WasteId;

final class AlertConfigurationItemDto
{
    /** @param array<string, mixed> $parameters */
    public function __construct(
        public readonly string $type,
        public readonly bool $enabled = false,
        public readonly ?PropertyId $propertyId = null,
        public readonly ?WasteId $wasteId = null,
        public readonly array $parameters = [],
    ) {}

    public function toItem(): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::from($this->type),
            enabled: $this->enabled,
            parameters: $this->parameters,
            propertyId: $this->propertyId,
            wasteId: $this->wasteId,
        );
    }
}
