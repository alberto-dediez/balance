<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CarbonFootprintReportRejected extends DomainEvent
{
    protected static string $eventName = 'carbonFootprintReportRejected';

    public function __construct(
        private readonly CarbonFootprintReportId $carbonFootprintReportId,
        private readonly UserId $reviewerId,
        private readonly string $comment,
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

    public function comment(): string
    {
        return $this->comment;
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
            'comment' => $this->comment,
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
            comment: $payload['comment'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
