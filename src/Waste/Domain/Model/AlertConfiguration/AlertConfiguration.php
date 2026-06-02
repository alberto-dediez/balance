<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Waste\Domain\Model\Alert\AlertType;

class AlertConfiguration implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<int, AlertConfigurationItem> */
    private iterable $items = [];

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private AlertConfigurationId $id,
        private ?AccountId $accountId = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): AlertConfigurationId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function items(): AlertConfigurationItems
    {
        return new AlertConfigurationItems($this->items);
    }

    public function isTypeEnabled(AlertType $type): bool
    {
        return $this->items()->getByType($type)?->enabled() ?? false;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @param array<string, array<string, mixed>> $items */
    public function register(AccountId $accountId, array $items): void
    {
        $this->recordApplyAndPublish(
            new AlertConfigurationRegistered(
                alertConfigurationId: $this->id(),
                accountId: $accountId,
                items: $items,
            ),
        );
    }

    /** @param array<string, array<string, mixed>> $items */
    public function update(array $items): void
    {
        $this->recordApplyAndPublish(
            new AlertConfigurationUpdated(
                alertConfigurationId: $this->id(),
                items: $items,
            ),
        );
    }

    public function applyAlertConfigurationRegistered(AlertConfigurationRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();

        foreach ($event->items() as $typeStr => $data) {
            $type = AlertType::from($typeStr);
            $this->items()->addItem(new AlertConfigurationItem(
                id: AlertConfigurationItemId::generate(),
                alertConfiguration: $this,
                type: $type,
                enabled: (bool) $data['enabled'],
                parameters: (array) ($data['parameters'] ?? []),
            ));
        }
    }

    public function applyAlertConfigurationUpdated(AlertConfigurationUpdated $event): void
    {
        $this->updatedAt = $event->occurredOn();

        foreach ($event->items() as $typeStr => $data) {
            $type = AlertType::from($typeStr);
            $existing = $this->items()->getByType($type);

            if (null !== $existing) {
                $existing->update(
                    enabled: (bool) $data['enabled'],
                    parameters: (array) ($data['parameters'] ?? []),
                );
            } else {
                $this->items()->addItem(new AlertConfigurationItem(
                    id: AlertConfigurationItemId::generate(),
                    alertConfiguration: $this,
                    type: $type,
                    enabled: (bool) $data['enabled'],
                    parameters: (array) ($data['parameters'] ?? []),
                ));
            }
        }
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
