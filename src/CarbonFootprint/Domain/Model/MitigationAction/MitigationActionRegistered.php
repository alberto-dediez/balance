<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MitigationActionRegistered extends DomainEvent
{
    protected static string $eventName = 'mitigationActionRegistered';

    public function __construct(
        private readonly MitigationActionId $mitigationActionId,
        private readonly CarbonFootprintReportSubjectType $subjectType,
        private readonly string $carbonFootprintId,
        private readonly AccountId $accountId,
        private readonly ?MitigationMeasureId $measureId,
        private readonly string $name,
        private readonly string $description,
        private readonly int $baseYear,
        private readonly int $targetYear,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mitigationActionId(): MitigationActionId
    {
        return $this->mitigationActionId;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function measureId(): ?MitigationMeasureId
    {
        return $this->measureId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function baseYear(): int
    {
        return $this->baseYear;
    }

    public function targetYear(): int
    {
        return $this->targetYear;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mitigationActionId;
    }

    public function payload(): array
    {
        return [
            'mitigationActionId' => (string) $this->mitigationActionId,
            'subjectType' => $this->subjectType->value,
            'carbonFootprintId' => $this->carbonFootprintId,
            'accountId' => (string) $this->accountId,
            'measureId' => null !== $this->measureId ? (string) $this->measureId : null,
            'name' => $this->name,
            'description' => $this->description,
            'baseYear' => $this->baseYear,
            'targetYear' => $this->targetYear,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $subjectType = CarbonFootprintReportSubjectType::tryFrom($payload['subjectType']);
        assert(null !== $subjectType);

        return new self(
            mitigationActionId: new MitigationActionId($payload['mitigationActionId']),
            subjectType: $subjectType,
            carbonFootprintId: $payload['carbonFootprintId'],
            accountId: new AccountId($payload['accountId']),
            measureId: null !== $payload['measureId'] ? new MitigationMeasureId($payload['measureId']) : null,
            name: $payload['name'],
            description: $payload['description'],
            baseYear: $payload['baseYear'],
            targetYear: $payload['targetYear'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
