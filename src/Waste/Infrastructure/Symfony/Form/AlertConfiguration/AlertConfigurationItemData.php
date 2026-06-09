<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\AlertConfiguration;

use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Alert\AlertPriority;
use App\Waste\Domain\Model\Waste\WasteId;

final class AlertConfigurationItemData
{
    /** @param array<string, array<string, mixed>> $parameters keyed by AlertType value */
    public function __construct(
        public ?string $type = null,
        public bool $enabled = true,
        public ?PropertyId $propertyId = null,
        public ?WasteId $wasteId = null,
        public array $parameters = [],
        public AlertPriority $priority = AlertPriority::Info,
    ) {}
}
