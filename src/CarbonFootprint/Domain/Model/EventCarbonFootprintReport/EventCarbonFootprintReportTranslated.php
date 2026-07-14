<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class EventCarbonFootprintReportTranslated extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintReportTranslated';

    public function __construct(
        private EventCarbonFootprintReportId $eventCarbonFootprintReportId,
        private string $filePath,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventCarbonFootprintReportId(): EventCarbonFootprintReportId
    {
        return $this->eventCarbonFootprintReportId;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventCarbonFootprintReportId;
    }

    public function payload(): array
    {
        return [
            'eventCarbonFootprintReportId' => (string) $this->eventCarbonFootprintReportId,
            'filePath' => $this->filePath,
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
            eventCarbonFootprintReportId: new EventCarbonFootprintReportId($payload['eventCarbonFootprintReportId']),
            filePath: $payload['filePath'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
