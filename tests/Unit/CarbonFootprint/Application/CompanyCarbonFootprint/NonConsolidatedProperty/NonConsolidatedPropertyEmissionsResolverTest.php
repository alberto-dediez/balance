<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyEmissions;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyEmissionsResolver;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOperatorContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class NonConsolidatedPropertyEmissionsResolverTest extends TestCase
{
    private const ACCOUNT_ID = 'account-1';
    private const COMPANY_ID = 'company-1';

    private InMemoryActivityRepository $activityRepository;
    private InMemoryPropertyRepository $propertyRepository;
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;
    private InMemoryOperatorContributionRepository $operatorContributionRepository;
    private NonConsolidatedPropertyEmissionsResolver $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->operatorContributionRepository = OperatorContributionRepositoryMother::inMemory();

        $this->sut = new NonConsolidatedPropertyEmissionsResolver(
            new CompanyProperties($this->propertyRepository),
            $this->propertyCarbonFootprintRepository,
            new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()), CountedActivityReferencesMother::create()),
            $this->operatorContributionRepository,
            new OperatorContributionEmissionsCalculator(new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()), CountedActivityReferencesMother::create())),
        );
    }

    public function testACompletedFootprintAddsScope1And2AndIgnoresScope3(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $propertyFootprint = $this->propertyFootprint('franchised-1');
        $this->addActivity($propertyFootprint, Scope::Scope1, Source::FixedFacilities, '3000');
        $this->addActivity($propertyFootprint, Scope::Scope2, Source::BuildingElectricity, '2000');
        $this->addActivity($propertyFootprint, Scope::Scope3, Source::PurchasedGoodsAndServices, '800');
        $propertyFootprint->complete(new Emissions('5800'));

        $resolved = $this->sut->resolve($this->companyFootprint());

        $this->assertCount(1, $resolved);
        $this->assertSame(Source::Franchises, $resolved[0]->source);
        $this->assertSame($propertyFootprint, $resolved[0]->origin);
        $this->assertSame(5000.0, (float) $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testANotCompletedFootprintCountsAsZero(): void
    {
        $this->addProperty('leased-out-1', OperatingRegime::LeasedOut);
        $propertyFootprint = $this->propertyFootprint('leased-out-1');
        $this->addActivity($propertyFootprint, Scope::Scope1, Source::FixedFacilities, '3000');

        $resolved = $this->sut->resolve($this->companyFootprint());

        $this->assertCount(1, $resolved);
        $this->assertSame(Source::DownstreamLeasedAssets, $resolved[0]->source);
        $this->assertNull($resolved[0]->origin);
        $this->assertSame('0', $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testAPropertyWithoutFootprintCountsAsZero(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);

        $resolved = $this->sut->resolve($this->companyFootprint());

        $this->assertCount(1, $resolved);
        $this->assertNull($resolved[0]->origin);
        $this->assertSame('0', $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testAFootprintOfAnotherAccountCountsAsZero(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $propertyFootprint = $this->propertyFootprint('franchised-1', 'other-account');
        $this->addActivity($propertyFootprint, Scope::Scope1, Source::FixedFacilities, '3000');
        $propertyFootprint->complete(new Emissions('3000'));

        $resolved = $this->sut->resolve($this->companyFootprint());

        $this->assertNull($resolved[0]->origin);
        $this->assertSame('0', $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testARemovedFootprintCountsAsZero(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $propertyFootprint = $this->propertyFootprint('franchised-1');
        $this->addActivity($propertyFootprint, Scope::Scope1, Source::FixedFacilities, '3000');
        $propertyFootprint->complete(new Emissions('3000'));
        $propertyFootprint->remove();

        $resolved = $this->sut->resolve($this->companyFootprint());

        $this->assertNull($resolved[0]->origin);
        $this->assertSame('0', $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testConsolidatedPropertiesAreLeftOut(): void
    {
        $this->addProperty('owned-1', OperatingRegime::Owned);
        $this->addProperty('managed-1', OperatingRegime::Managed);
        $this->addProperty('franchised-1', OperatingRegime::Franchised);

        $resolved = $this->sut->resolve($this->companyFootprint());

        $this->assertSame(['franchised-1'], array_map(static fn(NonConsolidatedPropertyEmissions $emissions): string => (string) $emissions->property->id(), $resolved));
    }

    public function testAnAnsweredOperatorContributionAddsItsScope1And2(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $companyFootprint = $this->companyFootprint();
        $contribution = $this->operatorContribution($companyFootprint, 'franchised-1');
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, '1000');
        $this->addActivity($contribution, Scope::Scope2, Source::BuildingElectricity, '3000');
        $contribution->answer();

        $resolved = $this->sut->resolve($companyFootprint);

        $this->assertCount(1, $resolved);
        $this->assertNull($resolved[0]->origin);
        $this->assertSame($contribution, $resolved[0]->contribution);
        $this->assertTrue($resolved[0]->fromOperatorContribution);
        $this->assertSame(4000.0, (float) $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testACompletedPropertyFootprintPrevailsOverTheOperatorContribution(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $companyFootprint = $this->companyFootprint();
        $propertyFootprint = $this->propertyFootprint('franchised-1');
        $this->addActivity($propertyFootprint, Scope::Scope1, Source::FixedFacilities, '3000');
        $propertyFootprint->complete(new Emissions('3000'));
        $contribution = $this->operatorContribution($companyFootprint, 'franchised-1');
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, '1000');
        $contribution->answer();

        $resolved = $this->sut->resolve($companyFootprint);

        $this->assertSame($propertyFootprint, $resolved[0]->origin);
        $this->assertSame($contribution, $resolved[0]->contribution);
        $this->assertFalse($resolved[0]->fromOperatorContribution);
        $this->assertSame(3000.0, (float) $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testAPendingOperatorContributionCountsAsZero(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $companyFootprint = $this->companyFootprint();
        $contribution = $this->operatorContribution($companyFootprint, 'franchised-1');
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, '1000');

        $resolved = $this->sut->resolve($companyFootprint);

        $this->assertSame($contribution, $resolved[0]->contribution);
        $this->assertFalse($resolved[0]->fromOperatorContribution);
        $this->assertSame('0', $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testAnIgnoredOperatorContributionCountsAsZero(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $companyFootprint = $this->companyFootprint();
        $contribution = $this->operatorContribution($companyFootprint, 'franchised-1');
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, '1000');
        $contribution->answer();
        $contribution->ignore();

        $resolved = $this->sut->resolve($companyFootprint);

        $this->assertFalse($resolved[0]->fromOperatorContribution);
        $this->assertSame('0', $resolved[0]->calculatedEmissions->co2eq());
    }

    public function testAnOperatorContributionOfAnotherCompanyFootprintIsIgnored(): void
    {
        $this->addProperty('franchised-1', OperatingRegime::Franchised);
        $contribution = $this->operatorContribution($this->companyFootprint(), 'franchised-1');
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, '1000');
        $contribution->answer();

        $resolved = $this->sut->resolve($this->companyFootprint());

        $this->assertNull($resolved[0]->contribution);
        $this->assertSame('0', $resolved[0]->calculatedEmissions->co2eq());
    }

    private function operatorContribution(CompanyCarbonFootprint $companyFootprint, string $propertyId): OperatorContribution
    {
        $contribution = OperatorContributionMother::pending(
            companyCarbonFootprintId: (string) $companyFootprint->id(),
            propertyId: $propertyId,
            accountId: self::ACCOUNT_ID,
        );
        $this->operatorContributionRepository->add($contribution);

        return $contribution;
    }

    private function addProperty(string $id, OperatingRegime $regime): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime($id, new CompanyId(self::COMPANY_ID), new PropertyId($id), $regime));
    }

    private function propertyFootprint(string $propertyId, string $accountId = self::ACCOUNT_ID): PropertyCarbonFootprint
    {
        $propertyFootprint = PropertyCarbonFootprintMother::withEmployeesAnswered($propertyId, (string) PropertyCarbonFootprintId::generate(), $accountId);
        $this->propertyCarbonFootprintRepository->add($propertyFootprint);

        return $propertyFootprint;
    }

    private function addActivity(PropertyCarbonFootprint|OperatorContribution $propertyFootprint, Scope $scope, Source $source, string $co2eq): void
    {
        $activity = CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId(self::ACCOUNT_ID),
            combustibleId: CombustibleId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: new Emissions($co2eq, $co2eq),
            quantity: ActivityMother::quantity(Unit::Liter, 100.0),
        );
        $this->activityRepository->save($activity);

        $propertyFootprint->addActivity($activity->id(), new ActivityPlacement($scope, $source));
    }

    private function companyFootprint(): CompanyCarbonFootprint
    {
        return CompanyCarbonFootprintMother::withEmployeesAnswered(self::COMPANY_ID, (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID);
    }
}
