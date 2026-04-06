<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ReasonTranslated extends DomainEvent
{
    protected static string $eventName = 'reasonTranslated';

    public function __construct(
        private readonly ReasonId $reasonId,
        private readonly string $description,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function reasonId(): ReasonId
    {
        return $this->reasonId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->reasonId;
    }

    public function payload(): array
    {
        return [
            'reasonId' => (string) $this->reasonId,
            'description' => $this->description,
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

        return new ReasonTranslated(
            reasonId: new ReasonId($payload['reasonId']),
            description: $payload['description'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
