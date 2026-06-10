<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Alert\AlertPriority;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Waste\WasteId;

class AlertConfigurationItem
{
    /**
     * Owning side of the relationship. Set by the aggregate via assignTo() when the item is added,
     * so it stays uninitialized only during the brief window between construction and registration.
     */
    private AlertConfiguration $alertConfiguration;

    /** @param array<string, mixed> $parameters */
    public function __construct(
        private AlertConfigurationItemId $id,
        private AlertType $type,
        private bool $enabled = true,
        private array $parameters = [],
        private ?PropertyId $propertyId = null,
        private ?WasteId $wasteId = null,
        private AlertPriority $priority = AlertPriority::Info,
    ) {}

    public function id(): AlertConfigurationItemId
    {
        return $this->id;
    }

    public function alertConfiguration(): AlertConfiguration
    {
        return $this->alertConfiguration;
    }

    public function assignTo(AlertConfiguration $alertConfiguration): void
    {
        $this->alertConfiguration = $alertConfiguration;
    }

    public function type(): AlertType
    {
        return $this->type;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /** @return array<string, mixed> */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function wasteId(): ?WasteId
    {
        return $this->wasteId;
    }

    public function priority(): AlertPriority
    {
        return $this->priority;
    }

    /** @param array<string, mixed> $parameters */
    public function update(bool $enabled, array $parameters, ?PropertyId $propertyId = null, ?WasteId $wasteId = null, AlertPriority $priority = AlertPriority::Info): void
    {
        $this->enabled = $enabled;
        $this->parameters = $parameters;
        $this->propertyId = $propertyId;
        $this->wasteId = $wasteId;
        $this->priority = $priority;
    }

    /** @return array{id: string, type: string, enabled: bool, propertyId: string|null, wasteId: string|null, parameters: array<string, mixed>, priority: string} */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type->value,
            'enabled' => $this->enabled,
            'propertyId' => $this->propertyId?->__toString(),
            'wasteId' => $this->wasteId?->__toString(),
            'parameters' => $this->parameters,
            'priority' => $this->priority->value,
        ];
    }

    /** @param array{id?: string, type: string, enabled: bool, propertyId: string|null, wasteId: string|null, parameters: array<string, mixed>, priority?: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? new AlertConfigurationItemId($data['id']) : AlertConfigurationItemId::generate(),
            type: AlertType::from((string) $data['type']),
            enabled: (bool) $data['enabled'],
            parameters: $data['parameters'],
            propertyId: null !== $data['propertyId'] ? new PropertyId($data['propertyId']) : null,
            wasteId: null !== $data['wasteId'] ? new WasteId($data['wasteId']) : null,
            priority: isset($data['priority']) ? AlertPriority::from($data['priority']) : AlertPriority::Info,
        );
    }
}
