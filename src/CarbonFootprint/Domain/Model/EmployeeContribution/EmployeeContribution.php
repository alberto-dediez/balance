<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class EmployeeContribution implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private EmployeeContributionId $id,
        private ?EventCarbonFootprintId $eventCarbonFootprintId = null,
        private ?AccountId $accountId = null,
        private ?EmployeeId $employeeId = null,
        #[\SensitiveParameter]
        private string $token = '',
        private EmployeeContributionStatus $status = EmployeeContributionStatus::Pending,
        private EmployeeContributionStepStatus $stepStatus = EmployeeContributionStepStatus::Started,
        private ?\DateTimeImmutable $answeredAt = null,
        private ?\DateTimeImmutable $ignoredAt = null,
        private ?string $employeeName = null,
        private bool $workedAssembly = false,
        private ?MobilityId $assemblyMobilityId = null,
        private ?float $assemblyDistanceKm = null,
        private ?int $assemblyVehicleOccupants = null,
        private ?int $assemblyDays = null,
        private ?int $assemblyHotelNights = null,
        private bool $workedCelebration = false,
        private ?MobilityId $celebrationMobilityId = null,
        private ?float $celebrationDistanceKm = null,
        private ?int $celebrationVehicleOccupants = null,
        private ?int $celebrationDays = null,
        private ?int $celebrationHotelNights = null,
        private bool $workedBreakdown = false,
        private ?MobilityId $breakdownMobilityId = null,
        private ?float $breakdownDistanceKm = null,
        private ?int $breakdownVehicleOccupants = null,
        private ?int $breakdownDays = null,
        private ?int $breakdownHotelNights = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): EmployeeContributionId
    {
        return $this->id;
    }

    public function eventCarbonFootprintId(): ?EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function employeeId(): ?EmployeeId
    {
        return $this->employeeId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function status(): EmployeeContributionStatus
    {
        return $this->status;
    }

    public function stepStatus(): EmployeeContributionStepStatus
    {
        return $this->stepStatus;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function ignoredAt(): ?\DateTimeImmutable
    {
        return $this->ignoredAt;
    }

    public function employeeName(): ?string
    {
        return $this->employeeName;
    }

    public function workedAssembly(): bool
    {
        return $this->workedAssembly;
    }

    public function assemblyMobilityId(): ?MobilityId
    {
        return $this->assemblyMobilityId;
    }

    public function assemblyDistanceKm(): ?float
    {
        return $this->assemblyDistanceKm;
    }

    public function assemblyVehicleOccupants(): ?int
    {
        return $this->assemblyVehicleOccupants;
    }

    public function assemblyDays(): ?int
    {
        return $this->assemblyDays;
    }

    public function assemblyHotelNights(): ?int
    {
        return $this->assemblyHotelNights;
    }

    public function workedCelebration(): bool
    {
        return $this->workedCelebration;
    }

    public function celebrationMobilityId(): ?MobilityId
    {
        return $this->celebrationMobilityId;
    }

    public function celebrationDistanceKm(): ?float
    {
        return $this->celebrationDistanceKm;
    }

    public function celebrationVehicleOccupants(): ?int
    {
        return $this->celebrationVehicleOccupants;
    }

    public function celebrationDays(): ?int
    {
        return $this->celebrationDays;
    }

    public function celebrationHotelNights(): ?int
    {
        return $this->celebrationHotelNights;
    }

    public function workedBreakdown(): bool
    {
        return $this->workedBreakdown;
    }

    public function breakdownMobilityId(): ?MobilityId
    {
        return $this->breakdownMobilityId;
    }

    public function breakdownDistanceKm(): ?float
    {
        return $this->breakdownDistanceKm;
    }

    public function breakdownVehicleOccupants(): ?int
    {
        return $this->breakdownVehicleOccupants;
    }

    public function breakdownDays(): ?int
    {
        return $this->breakdownDays;
    }

    public function breakdownHotelNights(): ?int
    {
        return $this->breakdownHotelNights;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function register(
        EventCarbonFootprintId $eventCarbonFootprintId,
        AccountId $accountId,
        EmployeeId $employeeId,
        #[\SensitiveParameter]
        string $token,
        ?string $employeeName,
    ): void {
        $this->recordApplyAndPublish(
            new EmployeeContributionRegistered(
                employeeContributionId: $this->id(),
                eventCarbonFootprintId: $eventCarbonFootprintId,
                accountId: $accountId,
                employeeId: $employeeId,
                token: $token,
                employeeName: $employeeName,
            ),
        );
    }

    public function answerPhase(
        Phase $phase,
        bool $worked,
        ?MobilityId $mobilityId,
        ?float $distanceKm,
        ?int $vehicleOccupants,
        ?int $days,
        ?int $hotelNights,
    ): void {
        if (Phase::Materials === $phase) {
            throw new \InvalidArgumentException('La fase de materiales no está disponible para empleados');
        }

        $this->recordApplyAndPublish(
            new EmployeeContributionPhaseAnswered(
                employeeContributionId: $this->id(),
                phase: $phase,
                worked: $worked,
                mobilityId: $mobilityId,
                distanceKm: $distanceKm,
                vehicleOccupants: $vehicleOccupants,
                days: $days,
                hotelNights: $hotelNights,
            ),
        );

        if (Phase::Breakdown === $phase) {
            $this->answer();
        }
    }

    public function answer(): void
    {
        if ($this->status->isAnswered()) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmployeeContributionAnswered(
                employeeContributionId: $this->id(),
            ),
        );
    }

    public function ignore(): void
    {
        if ($this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmployeeContributionIgnored(
                employeeContributionId: $this->id(),
            ),
        );
    }

    public function unignore(): void
    {
        if (!$this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmployeeContributionUnignored(
                employeeContributionId: $this->id(),
            ),
        );
    }

    public function resendNotification(): void
    {
        $this->recordApplyAndPublish(
            new EmployeeContributionNotificationResent(
                employeeContributionId: $this->id(),
            ),
        );
    }

    public function applyEmployeeContributionRegistered(EmployeeContributionRegistered $event): void
    {
        $this->eventCarbonFootprintId = $event->eventCarbonFootprintId();
        $this->accountId = $event->accountId();
        $this->employeeId = $event->employeeId();
        $this->token = $event->token();
        $this->employeeName = $event->employeeName();
        $this->status = EmployeeContributionStatus::Pending;
        $this->stepStatus = EmployeeContributionStepStatus::Started;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEmployeeContributionPhaseAnswered(EmployeeContributionPhaseAnswered $event): void
    {
        match ($event->phase()) {
            Phase::Assembly => $this->applyAssemblyAnswer($event),
            Phase::Celebration => $this->applyCelebrationAnswer($event),
            Phase::Breakdown => $this->applyBreakdownAnswer($event),
            Phase::Materials => throw new \InvalidArgumentException('La fase de materiales no está disponible para empleados'),
        };

        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeContributionAnswered(EmployeeContributionAnswered $event): void
    {
        $this->status = EmployeeContributionStatus::Answered;
        $this->advanceStepStatus(EmployeeContributionStepStatus::Completed);
        $this->answeredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeContributionIgnored(EmployeeContributionIgnored $event): void
    {
        $this->status = EmployeeContributionStatus::Ignored;
        $this->ignoredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeContributionUnignored(EmployeeContributionUnignored $event): void
    {
        $this->status = $this->answeredAt instanceof \DateTimeImmutable ? EmployeeContributionStatus::Answered : EmployeeContributionStatus::Pending;
        $this->ignoredAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeContributionNotificationResent(EmployeeContributionNotificationResent $event): void
    {
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    private function applyAssemblyAnswer(EmployeeContributionPhaseAnswered $event): void
    {
        $this->workedAssembly = $event->worked();
        $this->assemblyMobilityId = $event->mobilityId();
        $this->assemblyDistanceKm = $event->distanceKm();
        $this->assemblyVehicleOccupants = $event->vehicleOccupants();
        $this->assemblyDays = $event->days();
        $this->assemblyHotelNights = $event->hotelNights();
        $this->advanceStepStatus(EmployeeContributionStepStatus::AssemblyAnswered);
    }

    private function applyCelebrationAnswer(EmployeeContributionPhaseAnswered $event): void
    {
        $this->workedCelebration = $event->worked();
        $this->celebrationMobilityId = $event->mobilityId();
        $this->celebrationDistanceKm = $event->distanceKm();
        $this->celebrationVehicleOccupants = $event->vehicleOccupants();
        $this->celebrationDays = $event->days();
        $this->celebrationHotelNights = $event->hotelNights();
        $this->advanceStepStatus(EmployeeContributionStepStatus::CelebrationAnswered);
    }

    private function applyBreakdownAnswer(EmployeeContributionPhaseAnswered $event): void
    {
        $this->workedBreakdown = $event->worked();
        $this->breakdownMobilityId = $event->mobilityId();
        $this->breakdownDistanceKm = $event->distanceKm();
        $this->breakdownVehicleOccupants = $event->vehicleOccupants();
        $this->breakdownDays = $event->days();
        $this->breakdownHotelNights = $event->hotelNights();
        $this->advanceStepStatus(EmployeeContributionStepStatus::Completed);
    }

    private function advanceStepStatus(EmployeeContributionStepStatus $stepStatus): void
    {
        if ($this->stepStatus->isBefore($stepStatus)) {
            $this->stepStatus = $stepStatus;
        }
    }
}
