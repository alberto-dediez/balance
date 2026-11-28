<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ContestTranslated extends DomainEvent
{
    protected static string $eventName = 'contestTranslated';

    public function __construct(
        private readonly ContestId $contestId,
        private readonly string $name,
        private readonly string $description,
        private readonly string $prizeDescription,
        private readonly string $terms,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestId(): ContestId
    {
        return $this->contestId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function prizeDescription(): string
    {
        return $this->prizeDescription;
    }

    public function terms(): string
    {
        return $this->terms;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestId;
    }

    public function payload(): array
    {
        return [
            'contestId' => (string) $this->contestId,
            'name' => $this->name,
            'description' => $this->description,
            'prizeDescription' => $this->prizeDescription,
            'terms' => $this->terms,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestId: new ContestId($payload['contestId']),
            name: $payload['name'],
            description: $payload['description'],
            prizeDescription: $payload['prizeDescription'],
            terms: $payload['terms'],
            locale: Locale::from($payload['locale']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
