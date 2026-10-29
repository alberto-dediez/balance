<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertiesSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyAnswer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyEmissionsResolver;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class NonConsolidatedPropertiesSynchronizerTest extends TestCase
{
    private const ACCOUNT_ID = 'account-1';
    private const COMPANY_ID = 'company-1';

    private InMemoryActivityRepository $activityRepository;
    private InMemoryPropertyRepository $propertyRepository;
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;
    private NonConsolidatedPropertiesSynchronizer $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();

        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Propia', new CompanyId(self::COMPANY_ID), new PropertyId('owned-1'), OperatingRegime::Owned));
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia', new CompanyId(self::COMPANY_ID), new PropertyId('franchised-1'), OperatingRegime::Franchised));
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Arrendada', new CompanyId(self::COMPANY_ID), new PropertyId('leased-out-1'), OperatingRegime::LeasedOut));

        $this->sut = new NonConsolidatedPropertiesSynchronizer(new NonConsolidatedPropertyEmissionsResolver(
            new CompanyProperties($this->propertyRepository),
            $this->propertyCarbonFootprintRepository,
            new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()), CountedActivityReferencesMother::create()),
        ));
    }

    public function testRecordsEveryNonConsolidatedPropertyWithItsManualEmissions(): void
    {
        $footprint = $this->companyFootprint();

        $this->sut->sync($footprint, [new NonConsolidatedPropertyAnswer(new PropertyId('leased-out-1'), '4500')]);

        $lines = $this->linesByProperty($footprint);
        $this->assertCount(2, $lines);
        $this->assertSame('4500', $lines['leased-out-1']->emissions->co2eq());
        $this->assertSame(Source::DownstreamLeasedAssets, $lines['leased-out-1']->source);
        $this->assertNull($lines['franchised-1']->manualCo2eq);
        $this->assertSame(Source::Franchises, $lines['franchised-1']->source);
    }

    public function testWithoutAnswersKeepsTheSavedManualEmissionsAndRefreshesTheCalculatedOnes(): void
    {
        $footprint = $this->companyFootprint();
        $this->sut->sync($footprint, [new NonConsolidatedPropertyAnswer(new PropertyId('leased-out-1'), '4500')]);

        $propertyFootprint = PropertyCarbonFootprintMother::withEmployeesAnswered('franchised-1', (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $activity = CombustibleActivityMother::registered(new AccountId(self::ACCOUNT_ID));
        $this->activityRepository->save($activity);
        $propertyFootprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyFootprint->complete(new Emissions('15'));
        $this->propertyCarbonFootprintRepository->add($propertyFootprint);

        $this->sut->sync($footprint, null);

        $lines = $this->linesByProperty($footprint);
        $this->assertSame('4500', $lines['leased-out-1']->manualCo2eq);
        $this->assertSame(15.0, (float) $lines['franchised-1']->calculatedEmissions->co2eq());
        $this->assertSame((string) $propertyFootprint->id(), (string) $lines['franchised-1']->propertyCarbonFootprintId);
    }

    public function testAnAnswerOfAConsolidatedPropertyIsRejected(): void
    {
        $footprint = $this->companyFootprint();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property is not a non-consolidated property of the company');

        $this->sut->sync($footprint, [new NonConsolidatedPropertyAnswer(new PropertyId('owned-1'), '10')]);
    }

    public function testAPropertyThatBecomesOwnedIsRemoved(): void
    {
        $footprint = $this->companyFootprint();
        $this->sut->sync($footprint, []);

        $property = $this->propertyRepository->find(new PropertyId('franchised-1'));
        assert(null !== $property);
        $property->update(name: $property->name(), accountId: null, companyId: $property->companyId(), operatingRegime: OperatingRegime::Owned);

        $this->sut->sync($footprint, []);

        $this->assertSame(['leased-out-1'], array_keys($this->linesByProperty($footprint)));
    }

    /** @return array<string, NonConsolidatedPropertyLine> */
    private function linesByProperty(CompanyCarbonFootprint $footprint): array
    {
        $lines = [];
        foreach ($footprint->nonConsolidatedPropertyLines() as $line) {
            $lines[(string) $line->propertyId] = $line;
        }

        return $lines;
    }

    private function companyFootprint(): CompanyCarbonFootprint
    {
        return CompanyCarbonFootprintMother::withEmployeesAnswered(self::COMPANY_ID, (string) CompanyCarbonFootprintId::generate(), self::ACCOUNT_ID);
    }
}
