<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintTrait;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\StageNotExcludableException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\Collection;

class CompanyCarbonFootprint implements CarbonFootprint, \Stringable
{
    use AggregateRoot;
    use OrganizationalCarbonFootprintTrait;

    /** @var iterable<CompanyCarbonFootprintActivity> */
    private iterable $activities = [];

    /** @var iterable<CompanyCarbonFootprintExclusion> */
    private iterable $exclusions = [];

    /** @var iterable<CompanyCarbonFootprintNonConsolidatedProperty> */
    private iterable $nonConsolidatedProperties = [];

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private CompanyCarbonFootprintId $id,
        private ?CompanyId $companyId = null,
        private ?AccountId $accountId = null,
        private ?EmissionFactorGroupId $emissionFactorGroupId = null,
        private int $year = 0,
        private Emissions $totalEmissions = new Emissions(),
        private OrganizationalCarbonFootprintStatus $status = OrganizationalCarbonFootprintStatus::Started,
        private bool $mitigationPlanStarted = false,
        private bool $scope3Excluded = false,
        private ?ExclusionReason $scope3ExclusionReason = null,
        private ?string $scope3ExclusionComment = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): CompanyCarbonFootprintId
    {
        return $this->id;
    }

    public function companyId(): ?CompanyId
    {
        return $this->companyId;
    }

    public function register(
        CompanyId $companyId,
        AccountId $accountId,
        EmissionFactorGroupId $emissionFactorGroupId,
        int $year,
    ): void {
        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintRegistered(
                companyCarbonFootprintId: $this->id,
                companyId: $companyId,
                accountId: $accountId,
                emissionFactorGroupId: $emissionFactorGroupId,
                year: $year,
            ),
        );
    }

    public function answerScope(Scope $scope): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintScopeAnswered(
                companyCarbonFootprintId: $this->id,
                scope: $scope,
            ),
        );

        if (Scope::Scope3 === $scope && !$this->mitigationPlanStarted) {
            $this->recordApplyAndPublish(
                new CompanyCarbonFootprintMitigationPlanStarted(
                    companyCarbonFootprintId: $this->id,
                ),
            );
        }
    }

    public function addActivity(ActivityId $activityId, ActivityPlacement $placement): void
    {
        $this->ensureIsEditable();

        $scope = $placement->stage;
        assert($scope instanceof Scope);

        foreach ($this->activities as $activity) {
            if ($activity->activityId()->equals($activityId)) {
                return;
            }
        }

        if ($this->isStageExcluded($scope) || $this->isSourceExcluded($scope, $placement->source)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintActivityAdded(
                companyCarbonFootprintId: $this->id,
                activityId: $activityId,
                scope: $scope,
                source: $placement->source,
                propertyId: $placement->propertyId,
            ),
        );
    }

    public function removeActivity(ActivityId $activityId): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintActivityRemoved(
                companyCarbonFootprintId: $this->id,
                activityId: $activityId,
            ),
        );
    }

    public function excludeSource(CalculationStage $stage, Source $source, ExclusionReason $reason, ?string $comment = null): void
    {
        $this->ensureIsEditable();

        $scope = $stage;
        assert($scope instanceof Scope);

        if ($this->isStageExcluded($scope)) {
            return;
        }

        $existing = $this->exclusionFor($scope, $source);
        if (null !== $existing && $existing->reason() === $reason && $existing->comment() === $comment) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintSourceExcluded(
                companyCarbonFootprintId: $this->id,
                scope: $scope,
                source: $source,
                reason: $reason,
                comment: $comment,
            ),
        );

        $this->removeActivitiesOfSource($scope, $source);

        if (in_array($source, $scope->companyOnlySources(), true)) {
            foreach ($this->nonConsolidatedPropertiesOf() as $nonConsolidatedProperty) {
                if ($nonConsolidatedProperty->source() === $source) {
                    $this->removeNonConsolidatedProperty($nonConsolidatedProperty->propertyId());
                }
            }
        }
    }

    private function removeActivitiesOfSource(Scope $scope, Source $source): void
    {
        foreach ($this->activityReferences() as $reference) {
            if ($reference->placement->stage->key() === $scope->key() && $reference->placement->source === $source) {
                $this->removeActivity($reference->activityId);
            }
        }
    }

    public function includeSource(CalculationStage $stage, Source $source): void
    {
        $this->ensureIsEditable();

        $scope = $stage;
        assert($scope instanceof Scope);

        if (!$this->isSourceExcluded($scope, $source)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintSourceIncluded(
                companyCarbonFootprintId: $this->id,
                scope: $scope,
                source: $source,
            ),
        );
    }

    public function excludeStage(CalculationStage $stage, ExclusionReason $reason, ?string $comment = null): void
    {
        $this->ensureIsEditable();

        $scope = $stage;
        assert($scope instanceof Scope);

        if (!$scope->isExcludable()) {
            throw new StageNotExcludableException(sprintf('Stage "%s" cannot be excluded', $scope->key()));
        }

        if ($this->scope3Excluded && $this->scope3ExclusionReason === $reason && $this->scope3ExclusionComment === $comment) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintScopeExcluded(
                companyCarbonFootprintId: $this->id,
                scope: $scope,
                reason: $reason,
                comment: $comment,
            ),
        );

        foreach ($scope->sections() as $section) {
            $this->removeActivitiesOfSource($scope, $section->source);
        }

        foreach ($this->exclusionsFor($scope) as $exclusion) {
            $this->includeSource($scope, $exclusion->source());
        }

        foreach ($this->nonConsolidatedPropertiesOf() as $nonConsolidatedProperty) {
            $this->removeNonConsolidatedProperty($nonConsolidatedProperty->propertyId());
        }
    }

    public function includeStage(CalculationStage $stage): void
    {
        $this->ensureIsEditable();

        $scope = $stage;
        assert($scope instanceof Scope);

        if (!$this->isStageExcluded($scope)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintScopeIncluded(
                companyCarbonFootprintId: $this->id,
                scope: $scope,
            ),
        );
    }

    public function recordNonConsolidatedProperty(PropertyId $propertyId, Source $source, ?PropertyCarbonFootprintId $propertyCarbonFootprintId, Emissions $calculatedEmissions, ?string $manualCo2eq): void
    {
        $this->ensureIsEditable();

        if (!in_array($source, Scope::Scope3->companyOnlySources(), true)) {
            throw new \InvalidArgumentException(sprintf('Source "%s" is not a non-consolidated property source', $source->value));
        }

        if (null !== $manualCo2eq && (!is_numeric($manualCo2eq) || (float) $manualCo2eq < 0)) {
            throw new \InvalidArgumentException('Manual emissions must be a non-negative number');
        }

        $existing = $this->nonConsolidatedPropertyOf($propertyId);

        if ($this->isStageExcluded(Scope::Scope3) || $this->isSourceExcluded(Scope::Scope3, $source)) {
            if ($existing instanceof CompanyCarbonFootprintNonConsolidatedProperty) {
                $this->removeNonConsolidatedProperty($propertyId);
            }

            return;
        }

        if (
            $existing instanceof CompanyCarbonFootprintNonConsolidatedProperty
            && $existing->source() === $source
            && $this->sameOrigin($existing->propertyCarbonFootprintId(), $propertyCarbonFootprintId)
            && $existing->calculatedEmissions()->equals($calculatedEmissions)
            && $existing->manualCo2eq() === $manualCo2eq
        ) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintNonConsolidatedPropertyRecorded(
                companyCarbonFootprintId: $this->id,
                propertyId: $propertyId,
                source: $source,
                propertyCarbonFootprintId: $propertyCarbonFootprintId,
                calculatedEmissions: $calculatedEmissions,
                manualCo2eq: $manualCo2eq,
            ),
        );
    }

    /** @param list<PropertyId> $propertyIds */
    public function retainNonConsolidatedProperties(array $propertyIds): void
    {
        $this->ensureIsEditable();

        foreach ($this->nonConsolidatedPropertiesOf() as $nonConsolidatedProperty) {
            $kept = false;
            foreach ($propertyIds as $propertyId) {
                if ($propertyId->equals($nonConsolidatedProperty->propertyId())) {
                    $kept = true;

                    break;
                }
            }

            if (!$kept) {
                $this->removeNonConsolidatedProperty($nonConsolidatedProperty->propertyId());
            }
        }
    }

    /** @return list<NonConsolidatedPropertyLine> */
    public function nonConsolidatedPropertyLines(): array
    {
        $lines = [];

        foreach ($this->nonConsolidatedPropertiesOf() as $nonConsolidatedProperty) {
            $lines[] = $nonConsolidatedProperty->toLine();
        }

        return $lines;
    }

    private function removeNonConsolidatedProperty(PropertyId $propertyId): void
    {
        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintNonConsolidatedPropertyRemoved(
                companyCarbonFootprintId: $this->id,
                propertyId: $propertyId,
            ),
        );
    }

    /** @return list<CompanyCarbonFootprintNonConsolidatedProperty> */
    private function nonConsolidatedPropertiesOf(): array
    {
        $nonConsolidatedProperties = [];

        foreach ($this->nonConsolidatedProperties as $nonConsolidatedProperty) {
            $nonConsolidatedProperties[] = $nonConsolidatedProperty;
        }

        return $nonConsolidatedProperties;
    }

    private function nonConsolidatedPropertyOf(PropertyId $propertyId): ?CompanyCarbonFootprintNonConsolidatedProperty
    {
        foreach ($this->nonConsolidatedProperties as $nonConsolidatedProperty) {
            if ($nonConsolidatedProperty->propertyId()->equals($propertyId)) {
                return $nonConsolidatedProperty;
            }
        }

        return null;
    }

    private function sameOrigin(?PropertyCarbonFootprintId $current, ?PropertyCarbonFootprintId $other): bool
    {
        if (null === $current || null === $other) {
            return null === $current && null === $other;
        }

        return $current->equals($other);
    }

    private function detachNonConsolidatedProperty(PropertyId $propertyId): void
    {
        foreach ($this->nonConsolidatedProperties as $key => $nonConsolidatedProperty) {
            if (!$nonConsolidatedProperty->propertyId()->equals($propertyId)) {
                continue;
            }
            if (is_array($this->nonConsolidatedProperties)) {
                unset($this->nonConsolidatedProperties[$key]);
            } elseif ($this->nonConsolidatedProperties instanceof Collection) {
                $this->nonConsolidatedProperties->removeElement($nonConsolidatedProperty);
            }

            return;
        }
    }

    private function attachNonConsolidatedProperty(CompanyCarbonFootprintNonConsolidatedProperty $nonConsolidatedProperty): void
    {
        if (is_array($this->nonConsolidatedProperties)) {
            $this->nonConsolidatedProperties[] = $nonConsolidatedProperty;
        } elseif ($this->nonConsolidatedProperties instanceof Collection) {
            $this->nonConsolidatedProperties->add($nonConsolidatedProperty);
        }
    }

    public function complete(Emissions $totalEmissions): void
    {
        $this->ensureIsEditable();

        if (OrganizationalCarbonFootprintStatus::Scope3Answered !== $this->status) {
            throw new OrganizationalCarbonFootprintNotReadyToCompleteException('Organizational carbon footprint cannot be completed until scope 3 is answered');
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintCompleted(
                companyCarbonFootprintId: $this->id,
                totalEmissions: $totalEmissions,
            ),
        );
    }

    public function reopen(): void
    {
        if (!$this->status->isCompleted()) {
            throw new OrganizationalCarbonFootprintNotCompletedException('Only a completed organizational carbon footprint can be reopened');
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintReopened(
                companyCarbonFootprintId: $this->id,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintRemoved(
                companyCarbonFootprintId: $this->id,
            ),
        );
    }

    public function applyCompanyCarbonFootprintRegistered(CompanyCarbonFootprintRegistered $event): void
    {
        $this->companyId = $event->companyId();
        $this->accountId = $event->accountId();
        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
        $this->year = $event->year();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintScopeAnswered(CompanyCarbonFootprintScopeAnswered $event): void
    {
        $this->status = $this->status->advancedTo($event->scope()->answeredStatus());
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintMitigationPlanStarted(CompanyCarbonFootprintMitigationPlanStarted $event): void
    {
        $this->mitigationPlanStarted = true;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintActivityAdded(CompanyCarbonFootprintActivityAdded $event): void
    {
        $this->attachActivity(new CompanyCarbonFootprintActivity(
            id: CompanyCarbonFootprintActivityId::generate(),
            companyCarbonFootprint: $this,
            activityId: $event->activityId(),
            scope: $event->scope(),
            source: $event->source(),
            propertyId: $event->propertyId(),
            addedAt: $event->occurredOn(),
        ));
    }

    public function applyCompanyCarbonFootprintActivityRemoved(CompanyCarbonFootprintActivityRemoved $event): void
    {
        $this->detachActivity($event->activityId());
    }

    public function applyCompanyCarbonFootprintSourceExcluded(CompanyCarbonFootprintSourceExcluded $event): void
    {
        $existing = $this->exclusionFor($event->scope(), $event->source());

        if ($existing instanceof CompanyCarbonFootprintExclusion) {
            $existing->update($event->reason(), $event->comment(), $event->occurredOn());
        } else {
            $this->attachExclusion(new CompanyCarbonFootprintExclusion(
                id: CompanyCarbonFootprintExclusionId::generate(),
                companyCarbonFootprint: $this,
                scope: $event->scope(),
                source: $event->source(),
                reason: $event->reason(),
                comment: $event->comment(),
                excludedAt: $event->occurredOn(),
            ));
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintSourceIncluded(CompanyCarbonFootprintSourceIncluded $event): void
    {
        $this->detachExclusion($event->scope(), $event->source());
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintScopeExcluded(CompanyCarbonFootprintScopeExcluded $event): void
    {
        $this->scope3Excluded = true;
        $this->scope3ExclusionReason = $event->reason();
        $this->scope3ExclusionComment = $event->comment();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintScopeIncluded(CompanyCarbonFootprintScopeIncluded $event): void
    {
        $this->scope3Excluded = false;
        $this->scope3ExclusionReason = null;
        $this->scope3ExclusionComment = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintNonConsolidatedPropertyRecorded(CompanyCarbonFootprintNonConsolidatedPropertyRecorded $event): void
    {
        $existing = $this->nonConsolidatedPropertyOf($event->propertyId());

        if (null !== $existing) {
            $existing->update(
                source: $event->source(),
                propertyCarbonFootprintId: $event->propertyCarbonFootprintId(),
                calculatedEmissions: $event->calculatedEmissions(),
                manualCo2eq: $event->manualCo2eq(),
                recordedAt: $event->occurredOn(),
            );
        } else {
            $this->attachNonConsolidatedProperty(new CompanyCarbonFootprintNonConsolidatedProperty(
                id: CompanyCarbonFootprintNonConsolidatedPropertyId::generate(),
                companyCarbonFootprint: $this,
                propertyId: $event->propertyId(),
                source: $event->source(),
                propertyCarbonFootprintId: $event->propertyCarbonFootprintId(),
                calculatedEmissions: $event->calculatedEmissions(),
                manualCo2eq: $event->manualCo2eq(),
                recordedAt: $event->occurredOn(),
            ));
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintNonConsolidatedPropertyRemoved(CompanyCarbonFootprintNonConsolidatedPropertyRemoved $event): void
    {
        $this->detachNonConsolidatedProperty($event->propertyId());
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintCompleted(CompanyCarbonFootprintCompleted $event): void
    {
        $this->status = OrganizationalCarbonFootprintStatus::Completed;
        $this->totalEmissions = $event->totalEmissions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintReopened(CompanyCarbonFootprintReopened $event): void
    {
        $this->status = OrganizationalCarbonFootprintStatus::Scope3Answered;
        $this->totalEmissions = new Emissions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintRemoved(CompanyCarbonFootprintRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
