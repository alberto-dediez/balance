<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintTrait;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\StageNotExcludableException;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class PropertyCarbonFootprint implements CarbonFootprint, \Stringable
{
    use AggregateRoot;
    use OrganizationalCarbonFootprintTrait;

    /** @var iterable<PropertyCarbonFootprintActivity> */
    private iterable $activities = [];

    /** @var iterable<PropertyCarbonFootprintExclusion> */
    private iterable $exclusions = [];

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
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
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
        }
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

        foreach ($scope->sections() as $section) {
            $this->removeActivitiesOfSource($scope, $section->source);
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

    public function complete(Emissions $totalEmissions): void
    {
        $this->ensureIsEditable();

        if (OrganizationalCarbonFootprintStatus::ProvidersAnswered !== $this->status) {
            throw new OrganizationalCarbonFootprintNotReadyToCompleteException('Organizational carbon footprint cannot be completed until providers step is answered');
        }

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

    public function applyPropertyCarbonFootprintProvidersAnswered(PropertyCarbonFootprintProvidersAnswered $event): void
    {
        $this->status = $this->status->advancedTo(OrganizationalCarbonFootprintStatus::ProvidersAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintMitigationPlanStarted(PropertyCarbonFootprintMitigationPlanStarted $event): void
    {
        $this->mitigationPlanStarted = true;
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
        $this->status = OrganizationalCarbonFootprintStatus::ProvidersAnswered;
        $this->totalEmissions = new Emissions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintRemoved(PropertyCarbonFootprintRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
