<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasureCategory;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class MitigationMeasureCategoryTranslated extends DomainEvent
{
    protected static string $eventName = 'mitigationMeasureCategoryTranslated';

    public function __construct(
        private readonly MitigationMeasureCategoryId $mitigationMeasureCategoryId,
        private readonly string $name,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationMeasureCategoryId(): MitigationMeasureCategoryId
    {
        return $this->mitigationMeasureCategoryId;
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
        return $this->mitigationMeasureCategoryId;
    }

    public function payload(): array
    {
        return [
            'mitigationMeasureCategoryId' => (string) $this->mitigationMeasureCategoryId,
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

        return new self(
            mitigationMeasureCategoryId: new MitigationMeasureCategoryId($payload['mitigationMeasureCategoryId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
