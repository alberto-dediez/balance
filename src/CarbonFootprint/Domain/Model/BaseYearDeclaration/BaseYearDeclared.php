<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BaseYearDeclared extends DomainEvent
{
    protected static string $eventName = 'baseYearDeclared';

    public function __construct(
        private BaseYearDeclarationId $baseYearDeclarationId,
        private CarbonFootprintReportSubjectType $subjectType,
        private string $subjectId,
        private AccountId $accountId,
        private int $year,
        private string $justification,
        private string $recalculationThresholdPercent,
        private ?IntensityIndicator $primaryIndicator,
        private string $baseYearTotalKgCo2eq,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function baseYearDeclarationId(): BaseYearDeclarationId
    {
        return $this->baseYearDeclarationId;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function justification(): string
    {
        return $this->justification;
    }

    public function recalculationThresholdPercent(): string
    {
        return $this->recalculationThresholdPercent;
    }

    public function primaryIndicator(): ?IntensityIndicator
    {
        return $this->primaryIndicator;
    }

    public function baseYearTotalKgCo2eq(): string
    {
        return $this->baseYearTotalKgCo2eq;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->baseYearDeclarationId;
    }

    public function payload(): array
    {
        return [
            'baseYearDeclarationId' => (string) $this->baseYearDeclarationId,
            'subjectType' => $this->subjectType->value,
            'subjectId' => $this->subjectId,
            'accountId' => (string) $this->accountId,
            'year' => $this->year,
            'justification' => $this->justification,
            'recalculationThresholdPercent' => $this->recalculationThresholdPercent,
            'primaryIndicator' => $this->primaryIndicator?->value,
            'baseYearTotalKgCo2eq' => $this->baseYearTotalKgCo2eq,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            baseYearDeclarationId: new BaseYearDeclarationId($payload['baseYearDeclarationId']),
            subjectType: CarbonFootprintReportSubjectType::from($payload['subjectType']),
            subjectId: $payload['subjectId'],
            accountId: new AccountId($payload['accountId']),
            year: (int) $payload['year'],
            justification: $payload['justification'],
            recalculationThresholdPercent: $payload['recalculationThresholdPercent'],
            primaryIndicator: null !== ($payload['primaryIndicator'] ?? null) ? IntensityIndicator::from($payload['primaryIndicator']) : null,
            baseYearTotalKgCo2eq: $payload['baseYearTotalKgCo2eq'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
