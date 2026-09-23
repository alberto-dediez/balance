<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CarbonFootprintReportApproved extends DomainEvent
{
    protected static string $eventName = 'carbonFootprintReportApproved';

    /** @param array<string, string>|null $sections */
    public function __construct(
        private readonly CarbonFootprintReportId $carbonFootprintReportId,
        private readonly UserId $reviewerId,
        private readonly bool $automatic,
        private readonly ?array $sections,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function carbonFootprintReportId(): CarbonFootprintReportId
    {
        return $this->carbonFootprintReportId;
    }

    public function reviewerId(): UserId
    {
        return $this->reviewerId;
    }

    public function automatic(): bool
    {
        return $this->automatic;
    }

    /** @return array<string, string>|null */
    public function sections(): ?array
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
            'reviewerId' => (string) $this->reviewerId,
            'automatic' => $this->automatic,
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
            reviewerId: new UserId($payload['reviewerId']),
            automatic: $payload['automatic'],
            sections: $payload['sections'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
