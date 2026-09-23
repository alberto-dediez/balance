<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CarbonFootprintReportFailed extends DomainEvent
{
    protected static string $eventName = 'carbonFootprintReportFailed';

    public function __construct(
        private readonly CarbonFootprintReportId $carbonFootprintReportId,
        private readonly string $errorMessage,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function carbonFootprintReportId(): CarbonFootprintReportId
    {
        return $this->carbonFootprintReportId;
    }

    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->carbonFootprintReportId;
    }

    public function payload(): array
    {
        return [
            'carbonFootprintReportId' => (string) $this->carbonFootprintReportId,
            'errorMessage' => $this->errorMessage,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            carbonFootprintReportId: new CarbonFootprintReportId($payload['carbonFootprintReportId']),
            errorMessage: $payload['errorMessage'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
