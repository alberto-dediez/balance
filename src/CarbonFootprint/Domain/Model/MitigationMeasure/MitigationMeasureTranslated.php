<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class MitigationMeasureTranslated extends DomainEvent
{
    protected static string $eventName = 'mitigationMeasureTranslated';

    public function __construct(
        private readonly MitigationMeasureId $mitigationMeasureId,
        private readonly string $name,
        private readonly string $description,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationMeasureId(): MitigationMeasureId
    {
        return $this->mitigationMeasureId;
    }

    public function name(): string
    {
        return $this->name;
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
        return $this->mitigationMeasureId;
    }

    public function payload(): array
    {
        return [
            'mitigationMeasureId' => (string) $this->mitigationMeasureId,
            'name' => $this->name,
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

        return new self(
            mitigationMeasureId: new MitigationMeasureId($payload['mitigationMeasureId']),
            name: $payload['name'],
            description: $payload['description'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
