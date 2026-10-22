<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults\GetCompanyCarbonFootprintResultsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults\GetCompanyCarbonFootprintResultsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetCompanyCarbonFootprintResultsServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private GetCompanyCarbonFootprintResultsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new GetCompanyCarbonFootprintResultsService(
            $this->repository,
            PropertyRepositoryMother::inMemory(),
            new CompanyCarbonFootprintAuthorizer(),
            new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository)),
        );
    }

    public function testAggregatesTotalsByScopeAndBySource(): void
    {
        $footprint = $this->registeredFootprint();

        $this->givenACombustibleActivity($footprint, Scope::Scope1, Source::FixedFacilities, '180');
        $this->givenACombustibleActivity($footprint, Scope::Scope1, Source::OwnVehicles, '1250');
        $this->givenAnElectricityActivity($footprint, '2500', '2000');

        $response = ($this->sut)(new GetCompanyCarbonFootprintResultsRequest($footprint->id()), $this->ownerUser());

        $this->assertSame(3930.0, (float) $response->totalEmissions->co2eq());
        $this->assertSame(1430.0, (float) $response->emissionsByScope[Scope::Scope1->value]->co2eq());
        $this->assertSame(2500.0, (float) $response->emissionsByScope[Scope::Scope2->value]->co2eq());
        $this->assertSame(180.0, (float) $response->emissionsBySource[Source::FixedFacilities->value]->co2eq());
        $this->assertSame(1250.0, (float) $response->emissionsBySource[Source::OwnVehicles->value]->co2eq());
        $this->assertSame(2500.0, (float) $response->emissionsByScopeAndSource[Scope::Scope2->value][Source::BuildingElectricity->value]->co2eq());
        $this->assertSame(2000.0, (float) $response->locationBasedElectricityEmissions->co2eq());
        $this->assertArrayHasKey('', $response->emissionsByPropertyId);
        $this->assertSame(3930.0, (float) $response->emissionsByPropertyId['']->co2eq());
    }

    public function testExposesTheExclusions(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable, 'Sin datos');

        $response = ($this->sut)(new GetCompanyCarbonFootprintResultsRequest($footprint->id()), $this->ownerUser());

        $this->assertCount(1, $response->exclusions);
        $this->assertSame(Source::RefrigerantLeaks, $response->exclusions[0]->source());
        $this->assertSame('Sin datos', $response->exclusions[0]->comment());
    }

    public function testExposesTheScope3Exclusion(): void
    {
        $footprint = $this->registeredFootprint();
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'Sin datos');

        $response = ($this->sut)(new GetCompanyCarbonFootprintResultsRequest($footprint->id()), $this->ownerUser());

        $this->assertNotNull($response->scope3Exclusion);
        $this->assertSame(ExclusionReason::NoDataAvailable, $response->scope3Exclusion->reason);
        $this->assertSame('Sin datos', $response->scope3Exclusion->comment);
    }

    public function testTheScope3ExclusionIsNullWhenTheScopeIsIncluded(): void
    {
        $footprint = $this->registeredFootprint();

        $response = ($this->sut)(new GetCompanyCarbonFootprintResultsRequest($footprint->id()), $this->ownerUser());

        $this->assertNull($response->scope3Exclusion);
        $this->assertArrayHasKey(Scope::Scope3->value, $response->emissionsByScope);
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = $this->registeredFootprint('other-account-id');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetCompanyCarbonFootprintResultsRequest($footprint->id()), $this->ownerUser());
    }

    private function givenACombustibleActivity(CompanyCarbonFootprint $footprint, Scope $scope, Source $source, string $co2eq): void
    {
        $activity = CombustibleActivityMother::registered(new AccountId('account-id-test'));
        $activity->update($activity->combustibleId(), $activity->emissionFactor(), new Emissions($co2eq, $co2eq, '0', '0'), $activity->quantity());
        $this->activityRepository->save($activity);

        $footprint->addActivity($activity->id(), new ActivityPlacement($scope, $source));
    }

    private function givenAnElectricityActivity(CompanyCarbonFootprint $footprint, string $co2eq, string $locationBased): void
    {
        $activity = ElectricityActivityMother::registeredWithLocationBasedResult(new AccountId('account-id-test'));
        $activity->update(
            $activity->electricityMarketerId(),
            $activity->gdOFactorId(),
            $activity->emissionFactor(),
            new Emissions($co2eq, $co2eq, '0', '0'),
            $activity->quantity(),
            null,
            $activity->electricityMixId(),
            ElectricityActivityMother::locationBasedEmissionFactor(),
            new Emissions($locationBased, $locationBased, '0', '0'),
            locationBasedWellToTankEmissionFactor: ElectricityActivityMother::locationBasedWellToTankEmissionFactor(),
            locationBasedWellToTankEmissions: ElectricityActivityMother::locationBasedWellToTankEmissions(),
        );
        $this->activityRepository->save($activity);

        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
    }

    private function registeredFootprint(string $accountId = 'account-id-test'): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), $accountId);
        $this->repository->add($footprint);

        return $footprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
