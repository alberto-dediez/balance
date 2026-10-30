<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\RefrigerantGasCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ReportedEmissionsCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionActivitiesRemover;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOperatorContributionRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

abstract class OperatorContributionServiceTestCase extends TestCase
{
    protected const string ACCOUNT_ID = 'account-id';
    protected const string COMPANY_ID = 'company-id';

    protected InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprintRepository;
    protected InMemoryOperatorContributionRepository $contributionRepository;
    protected InMemoryPropertyRepository $propertyRepository;
    protected InMemoryActivityRepository $activityRepository;
    protected CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer;
    protected OperatorContributionAuthorizer $authorizer;
    protected OperatorContributionEmissionsCalculator $emissionsCalculator;
    protected OperatorContributionActivitiesRemover $activitiesRemover;
    protected StageSectionsBuilder $sectionsBuilder;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->companyCarbonFootprintRepository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->contributionRepository = OperatorContributionRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->companyCarbonFootprintAuthorizer = new CompanyCarbonFootprintAuthorizer();
        $this->authorizer = new OperatorContributionAuthorizer();
        $this->emissionsCalculator = new OperatorContributionEmissionsCalculator(new EmissionsBreakdownBuilder(
            $this->activityRepository,
            new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()),
            CountedActivityReferencesMother::create(),
        ));
        $this->activitiesRemover = new OperatorContributionActivitiesRemover($this->activityRepository);
        $this->sectionsBuilder = new StageSectionsBuilder(new ActivityCatalogProviderRegistry([
            new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
            new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
            new RefrigerantGasCatalogProvider(RefrigerantGasRepositoryMother::inMemory()),
            new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
            new ReportedEmissionsCatalogProvider(),
        ]), $this->activityRepository);
    }

    protected function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID));
    }

    protected function companyFootprint(): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered(self::COMPANY_ID, (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $this->companyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function completedCompanyFootprint(): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered(self::COMPANY_ID, (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $footprint->complete(new Emissions('1'));
        $this->companyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    protected function property(string $id = 'franchised-1', OperatingRegime $regime = OperatingRegime::Franchised, ?OperatorContact $contact = null): Property
    {
        $property = null !== $contact
            ? PropertyMother::fromNameAndCompanyIdAndIdAndRegimeAndContact('Hotel Franquiciado', new CompanyId(self::COMPANY_ID), new PropertyId($id), $regime, $contact)
            : PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Franquiciado', new CompanyId(self::COMPANY_ID), new PropertyId($id), $regime);
        $property->update(name: $property->name(), accountId: new AccountId(self::ACCOUNT_ID), companyId: $property->companyId());
        $this->propertyRepository->add($property);

        return $property;
    }

    protected function contribution(CompanyCarbonFootprint $footprint, string $propertyId = 'franchised-1'): OperatorContribution
    {
        $contribution = OperatorContributionMother::pending(
            companyCarbonFootprintId: (string) $footprint->id(),
            propertyId: $propertyId,
            accountId: self::ACCOUNT_ID,
        );
        $this->contributionRepository->add($contribution);

        return $contribution;
    }

    protected function addActivity(OperatorContribution $contribution, Scope $scope, Source $source, float $kilograms): ActivityId
    {
        $activity = ReportedEmissionsActivityMother::registered(new AccountId(self::ACCOUNT_ID), kilograms: $kilograms);
        $this->activityRepository->save($activity);

        $contribution->addActivity($activity->id(), new ActivityPlacement($scope, $source));

        return $activity->id();
    }
}
