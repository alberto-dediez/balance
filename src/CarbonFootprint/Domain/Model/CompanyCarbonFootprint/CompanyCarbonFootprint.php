<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintScope2NotAnsweredException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintTrait;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\StageNotExcludableException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\Collection;

class CompanyCarbonFootprint implements CarbonFootprint, \Stringable
{
    use AggregateRoot;
    use OrganizationalCarbonFootprintTrait {
        hasDataOfSource as private hasActivitiesOfSource;
    }

    /** @var iterable<CompanyCarbonFootprintActivity> */
    private iterable $activities = [];

    /** @var iterable<CompanyCarbonFootprintExclusion> */
    private iterable $exclusions = [];

    /** @var iterable<CompanyCarbonFootprintMaterialityDecision> */
    private iterable $materialityDecisions = [];

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
        private bool $precalculated = false,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
        $this->intensityDenominators = IntensityDenominators::empty();
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

        if (Scope::Scope3 === $scope && $this->scope3Excluded) {
            $this->answerProviders();
            $this->answerEmployees();
        }
    }

    public function answerMateriality(): void
    {
        $this->ensureIsEditable();

        if ($this->status->isMaterialityAnswered()) {
            return;
        }

        if (!$this->status->isScope2Answered()) {
            throw new OrganizationalCarbonFootprintScope2NotAnsweredException('Organizational carbon footprint materiality cannot be answered until scope 2 is answered');
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintMaterialityAnswered(
                companyCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerProviders(): void
    {
        $this->ensureIsEditable();

        if ($this->status->isProvidersAnswered()) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintProvidersAnswered(
                companyCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerEmployees(): void
    {
        $this->ensureIsEditable();

        if ($this->status->isEmployeesAnswered()) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintEmployeesAnswered(
                companyCarbonFootprintId: $this->id,
            ),
        );
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
                providerId: $placement->providerId,
                fromEmployees: $placement->fromEmployees,
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

        foreach ($scope->allSources() as $source) {
            $this->removeActivitiesOfSource($scope, $source);
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

    public function recordNonConsolidatedProperty(PropertyId $propertyId, Source $source, ?PropertyCarbonFootprintId $propertyCarbonFootprintId, ?OperatorContributionId $operatorContributionId, Emissions $calculatedEmissions, ?string $manualCo2eq): void
    {
        $this->ensureIsEditable();

        if (null !== $propertyCarbonFootprintId && null !== $operatorContributionId) {
            throw new \InvalidArgumentException('A non-consolidated property has at most one origin');
        }

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
            && $this->sameOrigin($existing->operatorContributionId(), $operatorContributionId)
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
                operatorContributionId: $operatorContributionId,
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

    public function materialitySourceIsPropagable(Source $source): bool
    {
        return true;
    }

    public function hasDataOfSource(Source $source): bool
    {
        if ($this->hasActivitiesOfSource($source)) {
            return true;
        }

        foreach ($this->nonConsolidatedPropertiesOf() as $nonConsolidatedProperty) {
            if ($nonConsolidatedProperty->source() === $source) {
                return true;
            }
        }

        return false;
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

    private function sameOrigin(PropertyCarbonFootprintId|OperatorContributionId|null $current, PropertyCarbonFootprintId|OperatorContributionId|null $other): bool
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

    /**
     * @param list<MaterialityDecisionInput> $decisions
     * @param array<string, string>          $omitCommentsBySource
     */
    public function applyMaterialityDecisions(array $decisions, array $omitCommentsBySource): void
    {
        if ($this->isCompleted()) {
            return;
        }

        $previousRecommendedExclusions = $this->recommendedExclusionsBySource();
        $revertedSources = $this->overriddenSourcesChangingVerdict($decisions);

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintMaterialityDecisionsApplied(
                companyCarbonFootprintId: $this->id,
                decisions: $this->materialityDecisionEntries($decisions),
            ),
        );

        foreach ($revertedSources as $source) {
            $this->recordApplyAndPublish(
                new CompanyCarbonFootprintMaterialityOverrideReverted(
                    companyCarbonFootprintId: $this->id,
                    source: $source,
                ),
            );
        }

        $this->propagateMaterialityDecisions($previousRecommendedExclusions, $omitCommentsBySource);
    }

    public function clearMaterialityDecisions(): void
    {
        if ($this->isCompleted() || [] === $this->materialityDecisions()) {
            return;
        }

        $this->includeRecommendedExclusions();

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintMaterialityDecisionsCleared(
                companyCarbonFootprintId: $this->id,
            ),
        );
    }

    public function overrideMaterialityDecision(Source $source, UserId $userId, string $justification): void
    {
        $this->ensureIsEditable();

        $decision = $this->materialityDecisionFor($source);

        if (null === $decision || ($decision->isOverridden() && $decision->overrideJustification() === $justification)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintMaterialityOverridden(
                companyCarbonFootprintId: $this->id,
                source: $source,
                verdict: $decision->verdict(),
                userId: $userId,
                justification: $justification,
            ),
        );
    }

    public function revertMaterialityOverride(Source $source): void
    {
        $this->ensureIsEditable();

        if (true !== $this->materialityDecisionFor($source)?->isOverridden()) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintMaterialityOverrideReverted(
                companyCarbonFootprintId: $this->id,
                source: $source,
            ),
        );
    }

    public function markAsPrecalculated(): void
    {
        $this->ensureIsEditable();

        if ($this->precalculated) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintMarkedAsPrecalculated(
                companyCarbonFootprintId: $this->id,
            ),
        );

        $this->removePrecalculatedSourcesActivities();
    }

    public function unmarkAsPrecalculated(): void
    {
        $this->ensureIsEditable();

        if (!$this->precalculated) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintUnmarkedAsPrecalculated(
                companyCarbonFootprintId: $this->id,
            ),
        );

        $this->removePrecalculatedSourcesActivities();
    }

    private function removePrecalculatedSourcesActivities(): void
    {
        $this->removeActivitiesOfStage(Scope::Scope1);
        $this->removeActivitiesOfStage(Scope::Scope2);
        $this->removeActivitiesOfSource(Scope::Scope3, Source::FuelAndEnergyRelated);
    }

    public function ensureCanBeCompleted(): void
    {
        $this->ensureIsEditable();

        if (OrganizationalCarbonFootprintStatus::EmployeesAnswered !== $this->status) {
            throw new OrganizationalCarbonFootprintNotReadyToCompleteException('Organizational carbon footprint cannot be completed until employees step is answered');
        }
    }

    public function complete(Emissions $totalEmissions): void
    {
        $this->ensureCanBeCompleted();

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

    public function declareIntensityDenominators(IntensityDenominators $denominators): void
    {
        if ($this->intensityDenominators->equals($denominators)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CompanyCarbonFootprintIntensityDenominatorsDeclared(
                companyCarbonFootprintId: $this->id,
                denominators: $denominators,
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

    public function applyCompanyCarbonFootprintMaterialityAnswered(CompanyCarbonFootprintMaterialityAnswered $event): void
    {
        $this->status = $this->status->advancedTo(OrganizationalCarbonFootprintStatus::MaterialityAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintProvidersAnswered(CompanyCarbonFootprintProvidersAnswered $event): void
    {
        $this->status = $this->status->advancedTo(OrganizationalCarbonFootprintStatus::ProvidersAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintEmployeesAnswered(CompanyCarbonFootprintEmployeesAnswered $event): void
    {
        $this->status = $this->status->advancedTo(OrganizationalCarbonFootprintStatus::EmployeesAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintMitigationPlanStarted(CompanyCarbonFootprintMitigationPlanStarted $event): void
    {
        $this->mitigationPlanStarted = true;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintMarkedAsPrecalculated(CompanyCarbonFootprintMarkedAsPrecalculated $event): void
    {
        $this->precalculated = true;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintUnmarkedAsPrecalculated(CompanyCarbonFootprintUnmarkedAsPrecalculated $event): void
    {
        $this->precalculated = false;
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
            providerId: $event->providerId(),
            fromEmployees: $event->fromEmployees(),
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
                operatorContributionId: $event->operatorContributionId(),
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
                operatorContributionId: $event->operatorContributionId(),
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
        $this->status = OrganizationalCarbonFootprintStatus::EmployeesAnswered;
        $this->totalEmissions = new Emissions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintIntensityDenominatorsDeclared(CompanyCarbonFootprintIntensityDenominatorsDeclared $event): void
    {
        $this->intensityDenominators = $event->denominators();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintRemoved(CompanyCarbonFootprintRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintMaterialityDecisionsApplied(CompanyCarbonFootprintMaterialityDecisionsApplied $event): void
    {
        $topicIds = [];

        foreach ($event->decisions() as $entry) {
            $input = MaterialityDecisionInput::fromPrimitives($entry);
            $source = null !== $entry['source'] ? Source::from($entry['source']) : null;
            $topicIds[] = $input->topicId;

            $existing = $this->materialityDecisionOfTopic($input->topicId);

            if ($existing instanceof CompanyCarbonFootprintMaterialityDecision) {
                $existing->recommend($input->topicNumber, $source, $input->scorePercent, $input->thresholdPercent, $input->forcedByHighMagnitude, $input->verdict, $event->occurredOn());

                continue;
            }

            $this->attachMaterialityDecision(new CompanyCarbonFootprintMaterialityDecision(
                id: CompanyCarbonFootprintMaterialityDecisionId::generate(),
                companyCarbonFootprint: $this,
                topicId: $input->topicId,
                topicNumber: $input->topicNumber,
                source: $source,
                scorePercent: $input->scorePercent,
                thresholdPercent: $input->thresholdPercent,
                forcedByHighMagnitude: $input->forcedByHighMagnitude,
                verdict: $input->verdict,
                recommendedAt: $event->occurredOn(),
            ));
        }

        foreach ($this->materialityDecisions() as $decision) {
            if (!in_array($decision->topicId(), $topicIds, true)) {
                $this->detachMaterialityDecision($decision->topicId());
            }
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintMaterialityDecisionsCleared(CompanyCarbonFootprintMaterialityDecisionsCleared $event): void
    {
        $this->detachAllMaterialityDecisions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintMaterialityOverridden(CompanyCarbonFootprintMaterialityOverridden $event): void
    {
        $decision = $this->materialityDecisionFor($event->source());

        if ($decision instanceof CompanyCarbonFootprintMaterialityDecision) {
            $decision->override($event->userId(), $event->justification(), $event->occurredOn());
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintMaterialityOverrideReverted(CompanyCarbonFootprintMaterialityOverrideReverted $event): void
    {
        $decision = $this->materialityDecisionFor($event->source());

        if ($decision instanceof CompanyCarbonFootprintMaterialityDecision) {
            $decision->revertOverride();
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
