<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Waste\WasteId;

final class ExistingAlertsIndex
{
    /** @var array<string, true> */
    private array $keys = [];

    /** @param Alert[] $alerts */
    public function __construct(array $alerts = [])
    {
        foreach ($alerts as $alert) {
            $this->add($alert->type(), $alert->propertyId(), $alert->wasteId(), $alert->periodTo(), $alert->priority());
        }
    }

    public function has(AlertType $type, ?PropertyId $propertyId, ?WasteId $wasteId, ?\DateTimeImmutable $checkDate = null, AlertPriority $alertPriority = AlertPriority::Info): bool
    {
        return isset($this->keys[self::key($type, $propertyId, $wasteId, $checkDate, $alertPriority)]);
    }

    public function add(AlertType $type, ?PropertyId $propertyId, ?WasteId $wasteId, ?\DateTimeImmutable $checkDate = null, AlertPriority $alertPriority = AlertPriority::Info): void
    {
        $this->keys[self::key($type, $propertyId, $wasteId, $checkDate, $alertPriority)] = true;
    }

    private static function key(AlertType $type, ?PropertyId $propertyId, ?WasteId $wasteId, ?\DateTimeImmutable $checkDate = null, AlertPriority $alertPriority = AlertPriority::Info): string
    {
        return $type->value
            . '|' . (null !== $propertyId ? (string) $propertyId : '')
            . '|' . (null !== $wasteId ? (string) $wasteId : '')
            . '|' . (null !== $checkDate ? $checkDate->format('Y-m-d') : '')
            . '|' . $alertPriority->value;
    }
}
