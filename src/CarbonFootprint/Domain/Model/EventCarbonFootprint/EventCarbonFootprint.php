<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class EventCarbonFootprint implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<EventCarbonFootprintActivity> */
    private iterable $activities = [];

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
        Emissions $totalEmissions,
        Emissions $emissionsPerAttendee,
        EventCarbonFootprintStatus $status,
    ): void {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintUpdated(
                eventCarbonFootprintId: $this->id(),
                calculationPeriod: $calculationPeriod,
                totalEmissions: $totalEmissions,
                emissionsPerAttendee: $emissionsPerAttendee,
                status: $status,
            ),
        );
    }

    public function answerMaterials(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintMaterialsAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerAssembly(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintAssemblyAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerCelebration(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintCelebrationAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function answerBreakdown(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintBreakdownAnswered(
                eventCarbonFootprintId: $this->id,
            ),
        );
    }

    public function complete(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintCompleted(
                eventCarbonFootprintId: $this->id,
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
        $this->totalEmissions = $event->totalEmissions();
        $this->emissionsPerAttendee = $event->emissionsPerAttendee();
        $this->status = $event->status();
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

    public function applyEventCarbonFootprintCompleted(EventCarbonFootprintCompleted $event): void
    {
        $this->status = EventCarbonFootprintStatus::Completed;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintRemoved(EventCarbonFootprintRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function addActivity(ActivityId $activityId, Phase $phase, Source $source): void
    {
        foreach ($this->activities as $activity) {
            if ($activity->activityId()->equals($activityId)) {
                return;
            }
        }

        $this->recordApplyAndPublish(
            new EventCarbonFootprintActivityAdded(
                eventCarbonFootprintId: $this->id,
                activityId: $activityId,
                phase: $phase,
                source: $source,
            ),
        );
    }

    public function removeActivity(ActivityId $activityId): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintActivityRemoved(
                eventCarbonFootprintId: $this->id,
                activityId: $activityId,
            ),
        );
    }

    public function activities(): EventCarbonFootprintActivities
    {
        return new EventCarbonFootprintActivities($this->activities);
    }

    public function applyEventCarbonFootprintActivityAdded(EventCarbonFootprintActivityAdded $event): void
    {
        $activity = new EventCarbonFootprintActivity(
            id: EventCarbonFootprintActivityId::generate(),
            eventCarbonFootprint: $this,
            activityId: $event->activityId(),
            phase: $event->phase(),
            source: $event->source(),
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
}
