<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintTrait;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class CompanyCarbonFootprint implements CarbonFootprint, \Stringable
{
    use AggregateRoot;
    use OrganizationalCarbonFootprintTrait;

    /** @var iterable<CompanyCarbonFootprintActivity> */
    private iterable $activities = [];

    /** @var iterable<CompanyCarbonFootprintExclusion> */
    private iterable $exclusions = [];

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private CompanyCarbonFootprintId $id,
        private ?CompanyId $companyId = null,
        private ?AccountId $accountId = null,
        private ?EmissionFactorGroupId $emissionFactorGroupId = null,
        private int $year = 0,
        private Emissions $totalEmissions = new Emissions(),
        private OrganizationalCarbonFootprintStatus $status = OrganizationalCarbonFootprintStatus::Started,
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

        if ($this->isSourceExcluded($scope, $placement->source)) {
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
        $this->detachExclusion($event->scope(), $event->source());

        $this->attachExclusion(new CompanyCarbonFootprintExclusion(
            id: CompanyCarbonFootprintExclusionId::generate(),
            companyCarbonFootprint: $this,
            scope: $event->scope(),
            source: $event->source(),
            reason: $event->reason(),
            comment: $event->comment(),
            excludedAt: $event->occurredOn(),
        ));

        $this->updatedAt = $event->occurredOn();
    }

    public function applyCompanyCarbonFootprintSourceIncluded(CompanyCarbonFootprintSourceIncluded $event): void
    {
        $this->detachExclusion($event->scope(), $event->source());
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
