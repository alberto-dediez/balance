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

    public function hasActiveItems(): bool
    {
        $activeItems = array_filter(iterator_to_array($this->items()->getIterator()), static fn(AlertConfigurationItem $item): bool => $item->enabled());

        return count($activeItems) > 0;
    }

    /** @return AlertConfigurationItem[] */
    public function getItemsForType(AlertType $type): array
    {
        return $this->items()->getAllByType($type);
    }

    /** @return AlertConfigurationItem[] */
    public function enabledItemsForType(AlertType $type): array
    {
        return array_values(array_filter(
            $this->getItemsForType($type),
            static fn(AlertConfigurationItem $item) => $item->enabled(),
        ));
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @param list<AlertConfigurationItem> $items */
    public function register(AccountId $accountId, array $items): void
    {
        self::guardAgainstDuplicateItems($items);

        $this->recordApplyAndPublish(
            new AlertConfigurationRegistered(
                alertConfigurationId: $this->id(),
                accountId: $accountId,
                items: $items,
            ),
        );
    }

    /** @param list<AlertConfigurationItem> $items */
    public function update(array $items): void
    {
        self::guardAgainstDuplicateItems($items);

        $this->recordApplyAndPublish(
            new AlertConfigurationUpdated(
                alertConfigurationId: $this->id(),
                items: $items,
            ),
        );
    }

    /**
     * Ensures there are no duplicate items for the same combination of type, property and waste.
     * The account is implicit, since every configuration belongs to a single account.
     *
     * @param list<AlertConfigurationItem> $items
     */
    private static function guardAgainstDuplicateItems(array $items): void
    {
        $seen = [];

        foreach ($items as $item) {
            $key = sprintf('%s|%s|%s|%s', $item->type()->value, $item->propertyId()?->__toString() ?? '', $item->wasteId()?->__toString() ?? '', $item->priority()->value);

            if (isset($seen[$key])) {
                throw new DuplicateAlertConfigurationItemException($item->type(), $item->propertyId()?->__toString(), $item->wasteId()?->__toString());
            }

            $seen[$key] = true;
        }
    }

    public function applyAlertConfigurationRegistered(AlertConfigurationRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();

        foreach ($event->items() as $item) {
            $item->assignTo($this);
            $this->items()->addItem($item);
        }
    }

    public function applyAlertConfigurationUpdated(AlertConfigurationUpdated $event): void
    {
        $this->updatedAt = $event->occurredOn();

        foreach (iterator_to_array($this->items()) as $item) {
            $this->items()->removeItem($item);
        }

        foreach ($event->items() as $item) {
            $item->assignTo($this);
            $this->items()->addItem($item);
        }
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
