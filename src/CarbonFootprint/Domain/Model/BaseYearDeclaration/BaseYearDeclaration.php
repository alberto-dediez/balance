<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class BaseYearDeclaration implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private BaseYearDeclarationId $id,
        private CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        private string $subjectId = '',
        private ?AccountId $accountId = null,
        private int $year = 0,
        private string $justification = '',
        private string $recalculationThresholdPercent = '5',
        private ?IntensityIndicator $primaryIndicator = null,
        private string $baseYearTotalKgCo2eq = '0',
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->events = $events;
    }

    public static function declare(
        BaseYearDeclarationId $id,
        CarbonFootprintReportSubjectType $subjectType,
        string $subjectId,
        AccountId $accountId,
        int $year,
        string $justification,
        string $threshold,
        ?IntensityIndicator $primaryIndicator,
        string $baseYearTotalKgCo2eq,
    ): self {
        $declaration = new self(id: $id);
        $declaration->recordDeclaration($subjectType, $subjectId, $accountId, $year, $justification, $threshold, $primaryIndicator, $baseYearTotalKgCo2eq);

        return $declaration;
    }

    public function redeclare(int $year, string $justification, string $threshold, ?IntensityIndicator $primaryIndicator, string $baseYearTotalKgCo2eq): void
    {
        $accountId = $this->accountId;
        assert(null !== $accountId);

        $this->recordDeclaration($this->subjectType, $this->subjectId, $accountId, $year, $justification, $threshold, $primaryIndicator, $baseYearTotalKgCo2eq);
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(new BaseYearDeclarationRemoved(baseYearDeclarationId: $this->id));
    }

    public function exceedsThreshold(float $currentTotal): bool
    {
        $snapshot = (float) $this->baseYearTotalKgCo2eq;

        if (0.0 === $snapshot) {
            return 0.0 !== $currentTotal;
        }

        return abs($currentTotal - $snapshot) / $snapshot * 100 > (float) $this->recalculationThresholdPercent;
    }

    public function changePercent(float $currentTotal): ?float
    {
        $snapshot = (float) $this->baseYearTotalKgCo2eq;

        if (0.0 === $snapshot) {
            return null;
        }

        return abs($currentTotal - $snapshot) / $snapshot * 100;
    }

    public function id(): BaseYearDeclarationId
    {
        return $this->id;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function accountId(): ?AccountId
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

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function applyBaseYearDeclared(BaseYearDeclared $event): void
    {
        if ('' === $this->subjectId) {
            $this->registeredAt = $event->occurredOn();
        }

        $this->subjectType = $event->subjectType();
        $this->subjectId = $event->subjectId();
        $this->accountId = $event->accountId();
        $this->year = $event->year();
        $this->justification = $event->justification();
        $this->recalculationThresholdPercent = $event->recalculationThresholdPercent();
        $this->primaryIndicator = $event->primaryIndicator();
        $this->baseYearTotalKgCo2eq = $event->baseYearTotalKgCo2eq();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyBaseYearDeclarationRemoved(BaseYearDeclarationRemoved $event): void
    {
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return sprintf('%s %s · %d', $this->subjectType->value, $this->subjectId, $this->year);
    }

    private function recordDeclaration(
        CarbonFootprintReportSubjectType $subjectType,
        string $subjectId,
        AccountId $accountId,
        int $year,
        string $justification,
        string $threshold,
        ?IntensityIndicator $primaryIndicator,
        string $baseYearTotalKgCo2eq,
    ): void {
        $justification = trim($justification);

        if ('' === $justification) {
            throw new \InvalidArgumentException('Base year justification cannot be empty');
        }

        if (!is_numeric($threshold) || (float) $threshold <= 0.0 || (float) $threshold > 100.0) {
            throw new \InvalidArgumentException('Recalculation threshold must be greater than 0 and at most 100');
        }

        $this->recordApplyAndPublish(
            new BaseYearDeclared(
                baseYearDeclarationId: $this->id,
                subjectType: $subjectType,
                subjectId: $subjectId,
                accountId: $accountId,
                year: $year,
                justification: $justification,
                recalculationThresholdPercent: $threshold,
                primaryIndicator: $primaryIndicator,
                baseYearTotalKgCo2eq: $baseYearTotalKgCo2eq,
            ),
        );
    }
}
