<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthorizer;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register\RegisterOrganizationalEmployeeContributionService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipation;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

abstract class OrganizationalEmployeeContributionServiceTestCase extends TestCase
{
    protected const string ACCOUNT_ID = 'account-id';
    protected const int YEAR = 2025;

    protected InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;
    protected InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprintRepository;
    protected InMemoryOrganizationalEmployeeContributionRepository $contributionRepository;
    protected InMemoryOrganizationalEmployeeParticipationRepository $participationRepository;
    protected InMemoryEmployeeRepository $employeeRepository;
    protected InMemoryPropertyRepository $propertyRepository;
    protected InMemoryActivityRepository $activityRepository;
    protected InMemoryMobilityRepository $mobilityRepository;
    protected InMemoryOvernightStayRepository $overnightStayRepository;
    protected OrganizationalCarbonFootprintLocator $locator;
    protected OrganizationalEmployeeContributionAuthorizer $authorizer;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->companyCarbonFootprintRepository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->contributionRepository = OrganizationalEmployeeContributionRepositoryMother::inMemory();
        $this->participationRepository = OrganizationalEmployeeParticipationRepositoryMother::inMemory();
        $this->employeeRepository = EmployeeRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->mobilityRepository = MobilityRepositoryMother::inMemory();
        $this->overnightStayRepository = new InMemoryOvernightStayRepository();
        $this->locator = OrganizationalCarbonFootprintLocatorMother::create($this->propertyCarbonFootprintRepository, $this->companyCarbonFootprintRepository);
        $this->authorizer = new OrganizationalEmployeeContributionAuthorizer();
    }

    protected function syncService(): SyncOrganizationalEmployeeActivitiesService
    {
        return SyncOrganizationalEmployeeActivitiesServiceMother::create(
            $this->locator,
            $this->participationRepository,
            $this->contributionRepository,
            $this->activityRepository,
            $this->mobilityRepository,
            $this->overnightStayRepository,
            $this->propertyRepository,
        );
    }

    protected function registerService(): RegisterOrganizationalEmployeeContributionService
    {
        return new RegisterOrganizationalEmployeeContributionService($this->locator, $this->employeeRepository, $this->contributionRepository);
    }

    protected function propertyFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
        $this->propertyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function completedPropertyFootprint(): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
        $footprint->complete(new Emissions());
        $this->propertyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function companyFootprint(CompanyId $companyId): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered((string) $companyId, (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID, self::YEAR);
        $this->companyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function consolidatedProperty(CompanyId $companyId, string $name = 'Hotel'): PropertyId
    {
        $propertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime($name, $companyId, $propertyId, OperatingRegime::Owned));

        return $propertyId;
    }

    protected function nonConsolidatedProperty(CompanyId $companyId, string $name = 'Franquicia'): PropertyId
    {
        $propertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime($name, $companyId, $propertyId, OperatingRegime::Franchised));

        return $propertyId;
    }

    protected function employee(string $accountId = self::ACCOUNT_ID, string $name = 'Ana'): Employee
    {
        $employee = EmployeeMother::from($name, strtolower($name) . '@example.com', (string) EmployeeId::generate(), $accountId);
        $this->employeeRepository->add($employee);

        return $employee;
    }

    protected function mobility(FactorBasis $factorBasis = FactorBasis::PerPassenger, string $name = 'Tren'): Mobility
    {
        return $this->mobilityWithFactorsFor([self::YEAR], $factorBasis, $name);
    }

    /** @param list<int> $years */
    protected function mobilityWithFactorsFor(array $years, FactorBasis $factorBasis = FactorBasis::PerPassenger, string $name = 'Tren'): Mobility
    {
        $mobility = MobilityMother::from($name, (string) MobilityId::generate(), factorBasis: $factorBasis);

        foreach ($years as $year) {
            $mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear($year));
        }

        $this->mobilityRepository->add($mobility);

        return $mobility;
    }

    protected function organizingTeamOvernightStay(int $year = self::YEAR): OvernightStayId
    {
        $overnightStay = OvernightStayMother::from('Hotel', (string) OvernightStayId::generate(), CategoryOvernight::OrganizingTeam);
        $overnightStay->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Pernoctacion)->withYear($year));
        $this->overnightStayRepository->add($overnightStay);

        return $overnightStay->id();
    }

    protected function participation(CarbonFootprint $footprint, CarbonFootprintReportSubjectType $subjectType, int $totalEmployees): OrganizationalEmployeeParticipation
    {
        $participation = OrganizationalEmployeeParticipationMother::withTotal($subjectType, (string) $footprint->id(), $totalEmployees, self::ACCOUNT_ID);
        $this->participationRepository->add($participation);

        return $participation;
    }

    protected function answeredContribution(
        CarbonFootprint $footprint,
        CarbonFootprintReportSubjectType $subjectType,
        Mobility $commute,
        ?Mobility $trip = null,
        int $hotelNights = 0,
        ?PropertyId $workplace = null,
    ): OrganizationalEmployeeContribution {
        $contribution = OrganizationalEmployeeContributionMother::answered(
            subjectType: $subjectType,
            carbonFootprintId: (string) $footprint->id(),
            commuteMobilityId: (string) $commute->id(),
            tripMobilityId: null !== $trip ? (string) $trip->id() : null,
            hotelNights: $hotelNights,
            workplacePropertyId: null !== $workplace ? (string) $workplace : null,
            accountId: self::ACCOUNT_ID,
        );
        $this->contributionRepository->add($contribution);

        return $contribution;
    }

    /** @return list<ActivityId> */
    protected function employeeActivityIds(CarbonFootprint $footprint, Source $source, ?PropertyId $propertyId = null): array
    {
        $ids = [];

        foreach ($footprint->activityReferences() as $reference) {
            $placement = $reference->placement;

            if (!$placement->fromEmployees || $placement->stage->key() !== Scope::Scope3->key() || $placement->source !== $source) {
                continue;
            }

            if ((string) $placement->propertyId !== (string) $propertyId) {
                continue;
            }

            $ids[] = $reference->activityId;
        }

        return $ids;
    }

    /** @return list<Activity> */
    protected function employeeActivities(CarbonFootprint $footprint, Source $source, ?PropertyId $propertyId = null): array
    {
        return array_values($this->activityRepository->findByIds($this->employeeActivityIds($footprint, $source, $propertyId)));
    }

    /** @return list<float> */
    protected function employeeQuantities(CarbonFootprint $footprint, Source $source, Unit $unit, ?PropertyId $propertyId = null): array
    {
        $quantities = [];

        foreach ($this->employeeActivities($footprint, $source, $propertyId) as $activity) {
            if ($activity->quantity()->unit() === $unit) {
                $quantities[] = round($activity->quantity()->value(), 6);
            }
        }

        sort($quantities);

        return $quantities;
    }

    protected function ownerUser(string $accountId = self::ACCOUNT_ID): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    protected function user(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId(self::ACCOUNT_ID));
    }
}
