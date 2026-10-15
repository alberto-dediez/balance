<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PurchaseServiceTranslated extends DomainEvent
{
    protected static string $eventName = 'purchaseServiceTranslated';

    public function __construct(
        private PurchaseServiceId $purchaseServiceId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function purchaseServiceId(): PurchaseServiceId
    {
        return $this->purchaseServiceId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->purchaseServiceId();
    }

    public function payload(): array
    {
        return [
            'purchaseServiceId' => (string) $this->purchaseServiceId,
            'name' => $this->name,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $locale = Locale::tryFrom($payload['locale']);
        assert(null !== $locale);

        return new PurchaseServiceTranslated(
            purchaseServiceId: new PurchaseServiceId($payload['purchaseServiceId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
