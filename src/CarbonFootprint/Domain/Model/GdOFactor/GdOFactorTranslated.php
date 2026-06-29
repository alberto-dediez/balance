<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class GdOFactorTranslated extends DomainEvent
{
    protected static string $eventName = 'gdOFactorTranslated';

    public function __construct(
        private GdOFactorId $gdOFactorId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function gdOFactorId(): GdOFactorId
    {
        return $this->gdOFactorId;
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
        return $this->gdOFactorId();
    }

    public function payload(): array
    {
        return [
            'gdOFactorId' => (string) $this->gdOFactorId,
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

        return new GdOFactorTranslated(
            gdOFactorId: new GdOFactorId($payload['gdOFactorId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
