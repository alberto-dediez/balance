<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class EventCarbonFootprint implements CarbonFootprint, \Stringable
{
    use AggregateRoot;

    /** @var iterable<EventCarbonFootprintActivity> */
    private iterable $activities = [];

    /** @var iterable<EventCarbonFootprintExclusion> */
    private iterable $exclusions = [];

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private EventCarbonFootprintId $id,
        private ?EventId $eventId = null,
        private ?AccountId $accountId = null,
        private ?EmissionFactorGroupId $emissionFactorGroupId = null,
        private DateRange $calculationPeriod = new DateRange(),
        private Emissions $totalEmissions = new Emissions(),
        private Emissions $emissionsPerAttendee = new Emissions(),
        private EventCarbonFootprintStatus $status = EventCarbonFootprintStatus::Started,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): EventCarbonFootprintId
    {
        return $this->id;
    }

    public function eventId(): ?EventId
    {
        return $this->eventId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function emissionFactorGroupId(): ?EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function calculationPeriod(): DateRange
    {
        return $this->calculationPeriod;
    }

    public static function calculatePeriod(Event $event): DateRange
    {
        return new DateRange(
            startDate: $event->assembly()->startDate(),
            endDate: $event->breakdown()->endDate(),
        );
    }

    public function calculationYear(): int
    {
        $referenceDate = $this->calculationPeriod->startDate()
            ?? $this->calculationPeriod->endDate()
            ?? $this->registeredAt;

        return (int) $referenceDate->format('Y');
    }

    public function totalEmissions(): Emissions
    {
        return $this->totalEmissions;
    }

    public function emissionsPerAttendee(): Emissions
    {
        return $this->emissionsPerAttendee;
    }

    public function status(): EventCarbonFootprintStatus
    {
        return $this->status;
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        EventId $eventId,
        AccountId $accountId,
        EmissionFactorGroupId $emissionFactorGroupId,
        DateRange $calculationPeriod,
        Emissions $totalEmissions,
        Emissions $emissionsPerAttendee,
        EventCarbonFootprintStatus $status,
    ): void {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintRegistered(
                eventCarbonFootprintId: $this->id(),
                eventId: $eventId,
                accountId: $accountId,
                emissionFactorGroupId: $emissionFactorGroupId,
                calculationPeriod: $calculationPeriod,
                totalEmissions: $totalEmissions,
                emissionsPerAttendee: $emissionsPerAttendee,
                status: $status,
            ),
        );
    }

    public function update(
        DateRange $calculationPeriod,
    ): void {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintUpdated(
                eventCarbonFootprintId: $this->id(),
                calculationPeriod: $calculationPeriod,
            ),
        );
    }

    public function answerPhase(Phase $phase): void
    {
        match ($phase) {
            Phase::Materials => $this->answerMaterials(),
            Phase::Assembly => $this->answerAssembly(),
            Phase::Celebration => $this->answerCelebration(),
            Phase::Breakdown => $this->answerBreakdown(),
        };
    }

    /** @return ActivityId[] */
    public function activityIds(ActivityPlacement $placement): array
    {
        return $this->activities()->filterByPlacement($placement)->activityIds();
    }

    /** @param ActivityId[] $keptIds */
    public function retainActivities(ActivityPlacement $placement, array $keptIds): void
    {
        $existingIds = $this->activityIds($placement);

        foreach ($existingIds as $existingId) {
            $kept = false;
            foreach ($keptIds as $keptId) {
                if ($keptId->equals($existingId)) {
                    $kept = true;

                    break;
                }
            }

            if (!$kept) {
                $this->removeActivity($existingId);
            }
        }
    }

    public function answerMaterials(): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintMaterialsAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerAssembly(): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintAssemblyAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerCelebration(): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintCelebrationAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerBreakdown(): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintBreakdownAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerProviders(): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintProvidersAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerEmployees(): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintEmployeesAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerAttendees(): void
    {
        $this->ensureIsEditable();

        $this->recordApplyAndPublish(
            new EventCarbonFootprintAttendeesAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function reopen(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintReopened(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function complete(
        Emissions $totalEmissions,
        Emissions $emissionsPerAttendee,
    ): void {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintCompleted(
                eventCarbonFootprintId: $this->id,
                totalEmissions: $totalEmissions,
                emissionsPerAttendee: $emissionsPerAttendee,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintRemoved(
                eventCarbonFootprintId: $this->id(),
            ),
        );
    }

    public function applyEventCarbonFootprintRegistered(EventCarbonFootprintRegistered $event): void
    {
        $this->eventId = $event->eventId();
        $this->accountId = $event->accountId();
        $this->emissionFactorGroupId = $event->emissionFactorGroupId();
        $this->calculationPeriod = $event->calculationPeriod();
        $this->totalEmissions = $event->totalEmissions();
        $this->emissionsPerAttendee = $event->emissionsPerAttendee();
        $this->status = $event->status();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintUpdated(EventCarbonFootprintUpdated $event): void
    {
        $this->calculationPeriod = $event->calculationPeriod();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintMaterialsAnswered(EventCarbonFootprintMaterialsAnswered $event): void
    {
        $this->status = EventCarbonFootprintStatus::MaterialsAnswered;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintAssemblyAnswered(EventCarbonFootprintAssemblyAnswered $event): void
    {
        $this->status = EventCarbonFootprintStatus::AssemblyAnswered;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintCelebrationAnswered(EventCarbonFootprintCelebrationAnswered $event): void
    {
        $this->status = EventCarbonFootprintStatus::CelebrationAnswered;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintBreakdownAnswered(EventCarbonFootprintBreakdownAnswered $event): void
    {
        $this->status = EventCarbonFootprintStatus::BreakdownAnswered;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintProvidersAnswered(EventCarbonFootprintProvidersAnswered $event): void
    {
        $this->status = EventCarbonFootprintStatus::ProvidersAnswered;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintEmployeesAnswered(EventCarbonFootprintEmployeesAnswered $event): void
    {
        $this->status = EventCarbonFootprintStatus::EmployeesAnswered;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintAttendeesAnswered(EventCarbonFootprintAttendeesAnswered $event): void
    {
        if (EventCarbonFootprintStatus::Completed !== $this->status) {
            $this->status = EventCarbonFootprintStatus::AttendeesAnswered;
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintReopened(EventCarbonFootprintReopened $event): void
    {
        $this->status = EventCarbonFootprintStatus::Started;
        $this->totalEmissions = new Emissions();
        $this->emissionsPerAttendee = new Emissions();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintCompleted(EventCarbonFootprintCompleted $event): void
    {
        $this->status = EventCarbonFootprintStatus::Completed;
        $this->totalEmissions = $event->totalEmissions();
        $this->emissionsPerAttendee = $event->emissionsPerAttendee();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintRemoved(EventCarbonFootprintRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function addActivity(ActivityId $activityId, ActivityPlacement $placement): void
    {
        $this->ensureIsEditable();

        $phase = $placement->stage;
        assert($phase instanceof Phase);

        foreach ($this->activities as $activity) {
            if ($activity->activityId()->equals($activityId)) {
                return;
            }
        }

        if ($this->exclusions()->isExcluded($phase, $placement->source)) {
            return;
        }

        $this->recordApplyAndPublish(
            new EventCarbonFootprintActivityAdded(
                eventCarbonFootprintId: $this->id,
                activityId: $activityId,
                phase: $phase,
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
            new EventCarbonFootprintActivityRemoved(
                eventCarbonFootprintId: $this->id,
                activityId: $activityId,
            ),
        );
    }

    public function removeEmployeeActivities(): void
    {
        foreach ($this->activities()->filterByFromEmployees(true)->activityIds() as $activityId) {
            $this->removeActivity($activityId);
        }
    }

    public function activities(): EventCarbonFootprintActivities
    {
        return new EventCarbonFootprintActivities($this->activities);
    }

    public function exclusions(): EventCarbonFootprintExclusions
    {
        return new EventCarbonFootprintExclusions($this->exclusions);
    }

    /** @return array<string, SourceExclusion> */
    public function exclusionsFor(CalculationStage $stage): array
    {
        assert($stage instanceof Phase);

        $indexed = [];

        foreach ($this->exclusions()->filterByPhase($stage)->toArray() as $exclusion) {
            $indexed[$exclusion->source()->value] = $exclusion;
        }

        return $indexed;
    }

    /** @return list<SourceExclusion> */
    public function allExclusions(): array
    {
        return $this->exclusions()->toArray();
    }

    /** @return list<ActivityReference> */
    public function activityReferences(): array
    {
        return array_map(
            static fn(EventCarbonFootprintActivity $activity) => new ActivityReference($activity->activityId(), $activity->placement()),
            array_values($this->activities()->toArray()),
        );
    }

    public function excludeSource(CalculationStage $stage, Source $source, ExclusionReason $reason, ?string $comment = null): void
    {
        $this->ensureIsEditable();

        $phase = $stage;
        assert($phase instanceof Phase);

        $existing = $this->exclusions()->forPhaseAndSource($phase, $source);
        if (null !== $existing && $existing->reason() === $reason && $existing->comment() === $comment) {
            return;
        }

        $this->recordApplyAndPublish(
            new EventCarbonFootprintSourceExcluded(
                eventCarbonFootprintId: $this->id,
                phase: $phase,
                source: $source,
                reason: $reason,
                comment: $comment,
            ),
        );

        $this->retainActivities(new ActivityPlacement($phase, $source), []);
    }

    public function includeSource(CalculationStage $stage, Source $source): void
    {
        $this->ensureIsEditable();

        $phase = $stage;
        assert($phase instanceof Phase);

        if (!$this->exclusions()->isExcluded($phase, $source)) {
            return;
        }

        $this->recordApplyAndPublish(
            new EventCarbonFootprintSourceIncluded(
                eventCarbonFootprintId: $this->id,
                phase: $phase,
                source: $source,
            ),
        );
    }

    public function applyEventCarbonFootprintSourceExcluded(EventCarbonFootprintSourceExcluded $event): void
    {
        $this->removeExclusionChild($event->phase(), $event->source());

        $exclusion = new EventCarbonFootprintExclusion(
            id: EventCarbonFootprintExclusionId::generate(),
            eventCarbonFootprint: $this,
            phase: $event->phase(),
            source: $event->source(),
            reason: $event->reason(),
            comment: $event->comment(),
            excludedAt: $event->occurredOn(),
        );

        if (is_array($this->exclusions)) {
            $this->exclusions[] = $exclusion;
        } elseif ($this->exclusions instanceof \Doctrine\Common\Collections\Collection) {
            $this->exclusions->add($exclusion);
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintSourceIncluded(EventCarbonFootprintSourceIncluded $event): void
    {
        $this->removeExclusionChild($event->phase(), $event->source());
        $this->updatedAt = $event->occurredOn();
    }

    private function removeExclusionChild(Phase $phase, Source $source): void
    {
        foreach ($this->exclusions as $key => $exclusion) {
            if ($exclusion->phase() !== $phase || $exclusion->source() !== $source) {
                continue;
            }
            if (is_array($this->exclusions)) {
                unset($this->exclusions[$key]);
            } elseif ($this->exclusions instanceof \Doctrine\Common\Collections\Collection) {
                $this->exclusions->removeElement($exclusion);
            }

            return;
        }
    }

    public function applyEventCarbonFootprintActivityAdded(EventCarbonFootprintActivityAdded $event): void
    {
        $activity = new EventCarbonFootprintActivity(
            id: EventCarbonFootprintActivityId::generate(),
            eventCarbonFootprint: $this,
            activityId: $event->activityId(),
            phase: $event->phase(),
            source: $event->source(),
            providerId: $event->providerId(),
            fromEmployees: $event->fromEmployees(),
            addedAt: $event->occurredOn(),
        );

        if (is_array($this->activities)) {
            $this->activities[] = $activity;
        } elseif ($this->activities instanceof \Doctrine\Common\Collections\Collection) {
            $this->activities->add($activity);
        }
    }

    public function applyEventCarbonFootprintActivityRemoved(EventCarbonFootprintActivityRemoved $event): void
    {
        foreach ($this->activities as $key => $activity) {
            if (!$activity->activityId()->equals($event->activityId())) {
                continue;
            }
            if (is_array($this->activities)) {
                unset($this->activities[$key]);
            } elseif ($this->activities instanceof \Doctrine\Common\Collections\Collection) {
                $this->activities->removeElement($activity);
            }

            return;
        }
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    private function ensureIsEditable(): void
    {
        if (EventCarbonFootprintStatus::Completed === $this->status) {
            throw new EventCarbonFootprintAlreadyCompletedException('EventCarbonFootprint is completed and can no longer be edited');
        }
    }
}
