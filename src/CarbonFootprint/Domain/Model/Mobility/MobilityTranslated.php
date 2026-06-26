<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class MobilityTranslated extends DomainEvent
{
    protected static string $eventName = 'mobilityTranslated';

    public function __construct(
        private MobilityId $mobilityId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mobilityId(): MobilityId
    {
        return $this->mobilityId;
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
        return $this->mobilityId();
    }

    public function payload(): array
    {
        return [
            'mobilityId' => (string) $this->mobilityId,
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

        return new MobilityTranslated(
            mobilityId: new MobilityId($payload['mobilityId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
