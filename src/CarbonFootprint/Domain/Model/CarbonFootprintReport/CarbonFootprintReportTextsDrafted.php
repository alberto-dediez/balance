<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CarbonFootprintReportTextsDrafted extends DomainEvent
{
    protected static string $eventName = 'carbonFootprintReportTextsDrafted';

    /** @param array<string, string> $sections */
    public function __construct(
        private readonly CarbonFootprintReportId $carbonFootprintReportId,
        private readonly array $sections,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function carbonFootprintReportId(): CarbonFootprintReportId
    {
        return $this->carbonFootprintReportId;
    }

    /** @return array<string, string> */
    public function sections(): array
    {
        return $this->sections;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->carbonFootprintReportId;
    }

    public function payload(): array
    {
        return [
            'carbonFootprintReportId' => (string) $this->carbonFootprintReportId,
            'sections' => $this->sections,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            carbonFootprintReportId: new CarbonFootprintReportId($payload['carbonFootprintReportId']),
            sections: $payload['sections'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
