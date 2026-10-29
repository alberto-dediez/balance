<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionStatus;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Consent\ConsentProof;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class OrganizationalEmployeeContribution implements \Stringable
{
    use AggregateRoot;

    public const int MAX_TRIPS = 1000;
    public const int MAX_VEHICLE_OCCUPANTS = 100;
    public const int MAX_HOTEL_NIGHTS = 366;

    /** @var iterable<OrganizationalEmployeeContributionCommute>|Collection<int, OrganizationalEmployeeContributionCommute> */
    private iterable $commutes = [];

    /** @var iterable<OrganizationalEmployeeContributionTrip>|Collection<int, OrganizationalEmployeeContributionTrip> */
    private iterable $trips = [];

    /** @param array<DomainEvent> $events */
    public function __construct(
        private OrganizationalEmployeeContributionId $id,
        private CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        private string $carbonFootprintId = '',
        private ?AccountId $accountId = null,
        private ?EmployeeId $employeeId = null,
        private ?string $employeeName = null,
        #[\SensitiveParameter]
        private string $token = '',
        private EmployeeContributionStatus $status = EmployeeContributionStatus::Pending,
        private OrganizationalEmployeeContributionStepStatus $stepStatus = OrganizationalEmployeeContributionStepStatus::Started,
        private ?PropertyId $workplacePropertyId = null,
        private ?int $commutingWeeksPerYear = null,
        private ?\DateTimeImmutable $answeredAt = null,
        private ?\DateTimeImmutable $ignoredAt = null,
        private ?ConsentProof $consentProof = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): OrganizationalEmployeeContributionId
    {
        return $this->id;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function employeeId(): ?EmployeeId
    {
        return $this->employeeId;
    }

    public function employeeName(): ?string
    {
        return $this->employeeName;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function status(): EmployeeContributionStatus
    {
        return $this->status;
    }

    public function stepStatus(): OrganizationalEmployeeContributionStepStatus
    {
        return $this->stepStatus;
    }

    public function workplacePropertyId(): ?PropertyId
    {
        return $this->workplacePropertyId;
    }

    public function commutingWeeksPerYear(): ?int
    {
        return $this->commutingWeeksPerYear;
    }

    public function answeredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function ignoredAt(): ?\DateTimeImmutable
    {
        return $this->ignoredAt;
    }

    public function consentProof(): ?ConsentProof
    {
        return $this->consentProof;
    }

    public function hasConsent(): bool
    {
        return null !== $this->consentProof?->consentedAt();
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<OrganizationalEmployeeContributionCommute> */
    public function commutes(): array
    {
        return array_values($this->commuteCollection()->toArray());
    }

    /** @return list<OrganizationalEmployeeContributionTrip> */
    public function trips(): array
    {
        return array_values($this->tripCollection()->toArray());
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function register(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        AccountId $accountId,
        EmployeeId $employeeId,
        #[\SensitiveParameter]
        string $token,
        ?string $employeeName,
    ): void {
        $this->recordApplyAndPublish(
            new OrganizationalEmployeeContributionRegistered(
                contributionId: $this->id,
                subjectType: $subjectType,
                carbonFootprintId: $carbonFootprintId,
                accountId: $accountId,
                employeeId: $employeeId,
                token: $token,
                employeeName: $employeeName,
            ),
        );
    }

    /** @param list<CommuteAnswer> $commutes */
    public function answerCommuting(?PropertyId $workplacePropertyId, ?int $weeksPerYear, array $commutes): void
    {
        if ([] !== $commutes && (null === $weeksPerYear || $weeksPerYear < 1 || $weeksPerYear > 53)) {
            throw new \InvalidArgumentException('Las semanas al año deben estar entre 1 y 53');
        }

        foreach ($commutes as $commute) {
            if ($commute->distanceKm <= 0) {
                throw new \InvalidArgumentException('Los kilómetros por trayecto deben ser mayores que 0');
            }

            if ($commute->daysPerWeek < 1 || $commute->daysPerWeek > 7) {
                throw new \InvalidArgumentException('Los días por semana deben estar entre 1 y 7');
            }

            if ($commute->vehicleOccupants < 1 || $commute->vehicleOccupants > self::MAX_VEHICLE_OCCUPANTS) {
                throw new \InvalidArgumentException('Los ocupantes del vehículo deben estar entre 1 y 100');
            }
        }

        $this->recordApplyAndPublish(
            new OrganizationalEmployeeContributionCommutingAnswered(
                contributionId: $this->id,
                workplacePropertyId: $workplacePropertyId,
                weeksPerYear: [] === $commutes ? null : $weeksPerYear,
                commutes: $commutes,
            ),
        );
    }

    /** @param list<TripAnswer> $trips */
    public function answerBusinessTravel(array $trips): void
    {
        foreach ($trips as $trip) {
            if ($trip->distanceKm <= 0) {
                throw new \InvalidArgumentException('Los kilómetros del viaje deben ser mayores que 0');
            }

            if ($trip->trips < 1 || $trip->trips > self::MAX_TRIPS) {
                throw new \InvalidArgumentException('El número de viajes debe estar entre 1 y 1000');
            }

            if ($trip->vehicleOccupants < 1 || $trip->vehicleOccupants > self::MAX_VEHICLE_OCCUPANTS) {
                throw new \InvalidArgumentException('Los ocupantes del vehículo deben estar entre 1 y 100');
            }

            if ($trip->hotelNights < 0 || $trip->hotelNights > self::MAX_HOTEL_NIGHTS) {
                throw new \InvalidArgumentException('Las noches de hotel deben estar entre 0 y 366');
            }
        }

        $this->recordApplyAndPublish(
            new OrganizationalEmployeeContributionBusinessTravelAnswered(
                contributionId: $this->id,
                trips: $trips,
            ),
        );

        $this->answer();
    }

    public function answer(): void
    {
        if ($this->status->isAnswered()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalEmployeeContributionAnswered(
                contributionId: $this->id,
            ),
        );
    }

    public function ignore(): void
    {
        if ($this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalEmployeeContributionIgnored(
                contributionId: $this->id,
            ),
        );
    }

    public function unignore(): void
    {
        if (!$this->status->isIgnored()) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalEmployeeContributionUnignored(
                contributionId: $this->id,
            ),
        );
    }

    public function resendNotification(): void
    {
        $this->recordApplyAndPublish(
            new OrganizationalEmployeeContributionNotificationResent(
                contributionId: $this->id,
            ),
        );
    }

    public function grantConsent(ConsentProof $consentProof): void
    {
        $this->recordApplyAndPublish(new OrganizationalEmployeeContributionConsentGranted($this->id, $consentProof));
    }

    public function applyOrganizationalEmployeeContributionRegistered(OrganizationalEmployeeContributionRegistered $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->carbonFootprintId = $event->carbonFootprintId();
        $this->accountId = $event->accountId();
        $this->employeeId = $event->employeeId();
        $this->token = $event->token();
        $this->employeeName = $event->employeeName();
        $this->status = EmployeeContributionStatus::Pending;
        $this->stepStatus = OrganizationalEmployeeContributionStepStatus::Started;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeContributionCommutingAnswered(OrganizationalEmployeeContributionCommutingAnswered $event): void
    {
        $this->workplacePropertyId = $event->workplacePropertyId();
        $this->commutingWeeksPerYear = $event->weeksPerYear();

        $collection = $this->commuteCollection();
        $collection->clear();

        foreach ($event->commutes() as $commute) {
            $collection->add(new OrganizationalEmployeeContributionCommute(
                id: OrganizationalEmployeeContributionCommuteId::generate(),
                contribution: $this,
                mobilityId: $commute->mobilityId,
                distanceKm: $commute->distanceKm,
                daysPerWeek: $commute->daysPerWeek,
                vehicleOccupants: $commute->vehicleOccupants,
            ));
        }

        $this->advanceStepStatus(OrganizationalEmployeeContributionStepStatus::CommutingAnswered);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeContributionBusinessTravelAnswered(OrganizationalEmployeeContributionBusinessTravelAnswered $event): void
    {
        $collection = $this->tripCollection();
        $collection->clear();

        foreach ($event->trips() as $trip) {
            $collection->add(new OrganizationalEmployeeContributionTrip(
                id: OrganizationalEmployeeContributionTripId::generate(),
                contribution: $this,
                mobilityId: $trip->mobilityId,
                distanceKm: $trip->distanceKm,
                trips: $trip->trips,
                vehicleOccupants: $trip->vehicleOccupants,
                hotelNights: $trip->hotelNights,
            ));
        }

        $this->advanceStepStatus(OrganizationalEmployeeContributionStepStatus::Completed);
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeContributionAnswered(OrganizationalEmployeeContributionAnswered $event): void
    {
        $this->status = EmployeeContributionStatus::Answered;
        $this->advanceStepStatus(OrganizationalEmployeeContributionStepStatus::Completed);
        $this->answeredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeContributionIgnored(OrganizationalEmployeeContributionIgnored $event): void
    {
        $this->status = EmployeeContributionStatus::Ignored;
        $this->ignoredAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeContributionUnignored(OrganizationalEmployeeContributionUnignored $event): void
    {
        $this->status = $this->answeredAt instanceof \DateTimeImmutable ? EmployeeContributionStatus::Answered : EmployeeContributionStatus::Pending;
        $this->ignoredAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeContributionNotificationResent(OrganizationalEmployeeContributionNotificationResent $event): void
    {
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeContributionConsentGranted(OrganizationalEmployeeContributionConsentGranted $event): void
    {
        $this->consentProof = $event->consentProof();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    private function advanceStepStatus(OrganizationalEmployeeContributionStepStatus $stepStatus): void
    {
        if ($this->stepStatus->isBefore($stepStatus)) {
            $this->stepStatus = $stepStatus;
        }
    }

    /** @return Collection<int, OrganizationalEmployeeContributionCommute> */
    private function commuteCollection(): Collection
    {
        if (!$this->commutes instanceof Collection) {
            $this->commutes = new ArrayCollection(
                $this->commutes instanceof \Traversable ? iterator_to_array($this->commutes) : $this->commutes,
            );
        }

        return $this->commutes;
    }

    /** @return Collection<int, OrganizationalEmployeeContributionTrip> */
    private function tripCollection(): Collection
    {
        if (!$this->trips instanceof Collection) {
            $this->trips = new ArrayCollection(
                $this->trips instanceof \Traversable ? iterator_to_array($this->trips) : $this->trips,
            );
        }

        return $this->trips;
    }
}
