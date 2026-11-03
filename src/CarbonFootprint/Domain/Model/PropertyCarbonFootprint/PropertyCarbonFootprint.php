<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintScope2NotAnsweredException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintTrait;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\StageNotExcludableException;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;

class PropertyCarbonFootprint implements CarbonFootprint, \Stringable
{
    use AggregateRoot;
    use OrganizationalCarbonFootprintTrait;

    /** @var iterable<PropertyCarbonFootprintActivity> */
    private iterable $activities = [];

    /** @var iterable<PropertyCarbonFootprintExclusion> */
    private iterable $exclusions = [];

    /** @var iterable<PropertyCarbonFootprintMaterialityDecision> */
    private iterable $materialityDecisions = [];

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private PropertyCarbonFootprintId $id,
        private ?PropertyId $propertyId = null,
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

    public function id(): PropertyCarbonFootprintId
    {
        return $this->id;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function register(
        PropertyId $propertyId,
        AccountId $accountId,
        EmissionFactorGroupId $emissionFactorGroupId,
        int $year,
    ): void {
        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintRegistered(
                propertyCarbonFootprintId: $this->id,
                propertyId: $propertyId,
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
            new PropertyCarbonFootprintScopeAnswered(
                propertyCarbonFootprintId: $this->id,
                scope: $scope,
            ),
        );

        if (Scope::Scope3 === $scope && !$this->mitigationPlanStarted) {
            $this->recordApplyAndPublish(
                new PropertyCarbonFootprintMitigationPlanStarted(
                    propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintMaterialityAnswered(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintProvidersAnswered(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintEmployeesAnswered(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintActivityAdded(
                propertyCarbonFootprintId: $this->id,
                activityId: $activityId,
                scope: $scope,
                source: $placement->source,
                providerId: $placement->providerId,
                fromEmployees: $placement->fromEmployees,
            ),
        );
    }

    public function removeActivity(ActivityId $activityId): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintActivityRemoved(
                propertyCarbonFootprintId: $this->id,
                activityId: $activityId,
            ),
        );
    }

    public function excludeSource(CalculationStage $stage, Source $source, ExclusionReason $reason, ?string $comment = null): void
    {
        $this->ensureIsEditable();

        $scope = $stage;
        assert($scope instanceof Scope);

        if (in_array($source, $scope->companyOnlySources(), true)) {
            return;
        }

        if ($this->isStageExcluded($scope)) {
            return;
        }

        $existing = $this->exclusionFor($scope, $source);
        if (null !== $existing && $existing->reason() === $reason && $existing->comment() === $comment) {
            return;
        }

        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintSourceExcluded(
                propertyCarbonFootprintId: $this->id,
                scope: $scope,
                source: $source,
                reason: $reason,
                comment: $comment,
            ),
        );

        $this->removeActivitiesOfSource($scope, $source);
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
            new PropertyCarbonFootprintSourceIncluded(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintScopeExcluded(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintScopeIncluded(
                propertyCarbonFootprintId: $this->id,
                scope: $scope,
            ),
        );
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
            new PropertyCarbonFootprintMaterialityDecisionsApplied(
                propertyCarbonFootprintId: $this->id,
                decisions: $this->materialityDecisionEntries($decisions),
            ),
        );

        foreach ($revertedSources as $source) {
            $this->recordApplyAndPublish(
                new PropertyCarbonFootprintMaterialityOverrideReverted(
                    propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintMaterialityDecisionsCleared(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintMaterialityOverridden(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintMaterialityOverrideReverted(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintMarkedAsPrecalculated(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintUnmarkedAsPrecalculated(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintCompleted(
                propertyCarbonFootprintId: $this->id,
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
            new PropertyCarbonFootprintReopened(
                propertyCarbonFootprintId: $this->id,
            ),
        );
    }

    public function declareIntensityDenominators(IntensityDenominators $denominators): void
    {
        if ($this->intensityDenominators->equals($denominators)) {
            return;
        }

        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintIntensityDenominatorsDeclared(
                propertyCarbonFootprintId: $this->id,
                denominators: $denominators,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintRemoved(
                propertyCarbonFootprintId: $this->id,
            ),
        );
    }

    public function applyPropertyCarbonFootprintRegistered(PropertyCarbonFootprintRegistered $event): void
    {
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
        $this->year = $event->year();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintScopeAnswered(PropertyCarbonFootprintScopeAnswered $event): void
    {
        $this->status = $this->status->advancedTo($event->scope()->answeredStatus());
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintMaterialityAnswered(PropertyCarbonFootprintMaterialityAnswered $event): void
    {
        $this->status = $this->status->advancedTo(OrganizationalCarbonFootprintStatus::MaterialityAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintProvidersAnswered(PropertyCarbonFootprintProvidersAnswered $event): void
    {
        $this->status = $this->status->advancedTo(OrganizationalCarbonFootprintStatus::ProvidersAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintEmployeesAnswered(PropertyCarbonFootprintEmployeesAnswered $event): void
    {
        $this->status = $this->status->advancedTo(OrganizationalCarbonFootprintStatus::EmployeesAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintMitigationPlanStarted(PropertyCarbonFootprintMitigationPlanStarted $event): void
    {
        $this->mitigationPlanStarted = true;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintMarkedAsPrecalculated(PropertyCarbonFootprintMarkedAsPrecalculated $event): void
    {
        $this->precalculated = true;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintUnmarkedAsPrecalculated(PropertyCarbonFootprintUnmarkedAsPrecalculated $event): void
    {
        $this->precalculated = false;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintActivityAdded(PropertyCarbonFootprintActivityAdded $event): void
    {
        $this->attachActivity(new PropertyCarbonFootprintActivity(
            id: PropertyCarbonFootprintActivityId::generate(),
            propertyCarbonFootprint: $this,
            activityId: $event->activityId(),
            scope: $event->scope(),
            source: $event->source(),
            providerId: $event->providerId(),
            fromEmployees: $event->fromEmployees(),
            addedAt: $event->occurredOn(),
        ));
    }

    public function applyPropertyCarbonFootprintActivityRemoved(PropertyCarbonFootprintActivityRemoved $event): void
    {
        $this->detachActivity($event->activityId());
    }

    public function applyPropertyCarbonFootprintSourceExcluded(PropertyCarbonFootprintSourceExcluded $event): void
    {
        $existing = $this->exclusionFor($event->scope(), $event->source());

        if ($existing instanceof PropertyCarbonFootprintExclusion) {
            $existing->update($event->reason(), $event->comment(), $event->occurredOn());
        } else {
            $this->attachExclusion(new PropertyCarbonFootprintExclusion(
                id: PropertyCarbonFootprintExclusionId::generate(),
                propertyCarbonFootprint: $this,
                scope: $event->scope(),
                source: $event->source(),
                reason: $event->reason(),
                comment: $event->comment(),
                excludedAt: $event->occurredOn(),
            ));
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintSourceIncluded(PropertyCarbonFootprintSourceIncluded $event): void
    {
        $this->detachExclusion($event->scope(), $event->source());
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintScopeExcluded(PropertyCarbonFootprintScopeExcluded $event): void
    {
        $this->scope3Excluded = true;
        $this->scope3ExclusionReason = $event->reason();
        $this->scope3ExclusionComment = $event->comment();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintScopeIncluded(PropertyCarbonFootprintScopeIncluded $event): void
    {
        $this->scope3Excluded = false;
        $this->scope3ExclusionReason = null;
        $this->scope3ExclusionComment = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintCompleted(PropertyCarbonFootprintCompleted $event): void
    {
        $this->status = OrganizationalCarbonFootprintStatus::Completed;
        $this->totalEmissions = $event->totalEmissions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintReopened(PropertyCarbonFootprintReopened $event): void
    {
        $this->status = OrganizationalCarbonFootprintStatus::EmployeesAnswered;
        $this->totalEmissions = new Emissions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintIntensityDenominatorsDeclared(PropertyCarbonFootprintIntensityDenominatorsDeclared $event): void
    {
        $this->intensityDenominators = $event->denominators();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintRemoved(PropertyCarbonFootprintRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintMaterialityDecisionsApplied(PropertyCarbonFootprintMaterialityDecisionsApplied $event): void
    {
        $topicIds = [];

        foreach ($event->decisions() as $entry) {
            $input = MaterialityDecisionInput::fromPrimitives($entry);
            $source = null !== $entry['source'] ? Source::from($entry['source']) : null;
            $topicIds[] = $input->topicId;

            $existing = $this->materialityDecisionOfTopic($input->topicId);

            if ($existing instanceof PropertyCarbonFootprintMaterialityDecision) {
                $existing->recommend($input->topicNumber, $source, $input->scorePercent, $input->thresholdPercent, $input->forcedByHighMagnitude, $input->verdict, $event->occurredOn());

                continue;
            }

            $this->attachMaterialityDecision(new PropertyCarbonFootprintMaterialityDecision(
                id: PropertyCarbonFootprintMaterialityDecisionId::generate(),
                propertyCarbonFootprint: $this,
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

    public function applyPropertyCarbonFootprintMaterialityDecisionsCleared(PropertyCarbonFootprintMaterialityDecisionsCleared $event): void
    {
        $this->detachAllMaterialityDecisions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintMaterialityOverridden(PropertyCarbonFootprintMaterialityOverridden $event): void
    {
        $decision = $this->materialityDecisionFor($event->source());

        if ($decision instanceof PropertyCarbonFootprintMaterialityDecision) {
            $decision->override($event->userId(), $event->justification(), $event->occurredOn());
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintMaterialityOverrideReverted(PropertyCarbonFootprintMaterialityOverrideReverted $event): void
    {
        $decision = $this->materialityDecisionFor($event->source());

        if ($decision instanceof PropertyCarbonFootprintMaterialityDecision) {
            $decision->revertOverride();
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
