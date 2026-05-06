<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\SurplusCause;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class SurplusCauseTranslated extends DomainEvent
{
    protected static string $eventName = 'surplusCauseTranslated';

    public function __construct(
        private readonly SurplusCauseId $surplusCauseId,
        private readonly string $name,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
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
        return $this->surplusCauseId;
    }

    public function payload(): array
    {
        return [
            'surplusCauseId' => (string) $this->surplusCauseId,
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

        return new SurplusCauseTranslated(
            surplusCauseId: new SurplusCauseId($payload['surplusCauseId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
