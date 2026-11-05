<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint\Export;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CapitalGoodCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MaterialCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\OvernightStayCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\WasteCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRow;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowsBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowType;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ActivityItemNameResolver;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ProviderNameResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data\OrganizationalCollectorContextTrait;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\OvernightStayActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\TransportActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\WasteActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class CarbonFootprintDetailRowsBuilderTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    private InMemoryOrganizationalProviderContributionRepository $contributionRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryPropertyRepository $propertyRepository;
    private InMemoryUserRepository $users;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->initActivities();
        $this->contributionRepository = OrganizationalProviderContributionRepositoryMother::inMemory();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->users = new InMemoryUserRepository();
    }

    public function testBuildsOneActivityRowPerActivityOfThePropertyFootprint(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
        $combustible = $this->save(CombustibleActivityMother::registered());
        $electricity = $this->save(ElectricityActivityMother::registered());
        $material = $this->save(MaterialActivityMother::registered());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->addActivity($material->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(3, $rows);
        $this->assertSame(CarbonFootprintDetailRowType::Activity, $rows[0]->rowType);
        $this->assertSame(Scope::Scope1, $rows[0]->stage);
        $this->assertSame(Source::FixedFacilities, $rows[0]->source);
        $this->assertSame(ActivityType::Combustible, $rows[0]->activityType);
        $this->assertSame('combustible', $rows[0]->itemName);
        $this->assertSame(200.0, $rows[0]->quantity);
        $this->assertSame(Unit::Liter, $rows[0]->unit);
        $this->assertSame('1.5', $rows[0]->factorCo2eq);
        $this->assertSame(Unit::Kilogram, $rows[0]->factorUnit);
        $this->assertSame(2024, $rows[0]->factorYear);
        $this->assertSame('15.0', $rows[0]->emissionsCo2eq);
        $this->assertSame((string) $combustible->id(), $rows[0]->activityId);
        $this->assertSame(Scope::Scope2, $rows[1]->stage);
        $this->assertSame(Scope::Scope3, $rows[2]->stage);
        $this->assertSame(ItemCondition::New, $rows[2]->condition);
        $this->assertNull($rows[0]->propertyName);
    }

    public function testResolvesTheProviderAndMarksTheEmployeeActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
        $provider = ProviderMother::fromNameAndId('Logística ACME', (string) ProviderId::generate());
        $this->providerRepository->add($provider);
        $reported = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto semanal'));
        $commuting = $this->save(ReportedEmissionsActivityMother::registered(description: 'Desplazamientos'));
        $footprint->addActivity($reported->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id()));
        $footprint->addActivity($commuting->id(), new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(2, $rows);
        $this->assertSame('Logística ACME', $rows[0]->providerName);
        $this->assertFalse($rows[0]->fromEmployees);
        $this->assertSame('Reparto semanal', $rows[0]->dataSource);
        $this->assertNull($rows[1]->providerName);
        $this->assertTrue($rows[1]->fromEmployees);
    }

    public function testLeavesOutTheActivitiesOfAnIgnoredProviderContribution(): void
    {
        $footprintId = (string) PropertyCarbonFootprintId::generate();
        $footprint = PropertyCarbonFootprintMother::from('P-1', $footprintId);
        $providerId = ProviderId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $providerId, sources: [Source::PurchasedGoodsAndServices]);
        $contribution->ignore();
        $this->contributionRepository->add($contribution);
        $owner = $this->save(MaterialActivityMother::registered());
        $reported = $this->save(ReportedEmissionsActivityMother::registered());
        $footprint->addActivity($owner->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $footprint->addActivity($reported->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices, providerId: $providerId));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(1, $rows);
        $this->assertSame((string) $owner->id(), $rows[0]->activityId);
    }

    public function testAddsADerivedFuelAndEnergyRowForTheWellToTankOfAScope1Combustible(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
        $combustible = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(2, $rows);
        $this->assertSame('122.0', $rows[0]->wellToTankCo2eq);
        $this->assertSame(CarbonFootprintDetailRowType::DerivedFuelAndEnergy, $rows[1]->rowType);
        $this->assertSame(Scope::Scope3, $rows[1]->stage);
        $this->assertSame(Source::FuelAndEnergyRelated, $rows[1]->source);
        $this->assertSame(ActivityType::Combustible, $rows[1]->activityType);
        $this->assertSame('combustible', $rows[1]->itemName);
        $this->assertSame('122.0', $rows[1]->emissionsCo2eq);
        $this->assertNull($rows[1]->quantity);
    }

    public function testDoesNotAddDerivedRowsWhenTheFuelAndEnergyCategoryIsExcluded(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
        $combustible = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->excludeSource(Scope::Scope3, Source::FuelAndEnergyRelated, ExclusionReason::NotSignificant);

        $rows = $this->createSUT()->build($footprint);

        $this->assertSame(
            [CarbonFootprintDetailRowType::Activity, CarbonFootprintDetailRowType::Exclusion],
            array_map(static fn(CarbonFootprintDetailRow $row): CarbonFootprintDetailRowType => $row->rowType, $rows),
        );
    }

    public function testTheWellToTankOfAScope1ActivityIsEmptyWhenTheFuelAndEnergyCategoryIsExcluded(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
        $combustible = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->excludeSource(Scope::Scope3, Source::FuelAndEnergyRelated, ExclusionReason::NotSignificant);

        $rows = $this->createSUT()->build($footprint);

        $this->assertSame(CarbonFootprintDetailRowType::Activity, $rows[0]->rowType);
        $this->assertNull($rows[0]->wellToTankCo2eq);
    }

    public function testTheWellToTankOfAScope1ActivityIsEmptyWhenTheScope3IsExcluded(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('P-1', (string) PropertyCarbonFootprintId::generate());
        $combustible = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'Sin datos');

        $rows = $this->createSUT()->build($footprint);

        $this->assertSame(CarbonFootprintDetailRowType::Activity, $rows[0]->rowType);
        $this->assertNull($rows[0]->wellToTankCo2eq);
    }

    public function testAnActivityRowOfTheScope3CarriesTheMaterialityDecisionOfItsCategory(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::calculate(1, 62.5)]);
        $material = $this->save(MaterialActivityMother::registered());
        $footprint->addActivity($material->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $row = $this->rowsByActivityId($this->createSUT()->build($footprint))[(string) $material->id()];

        $this->assertSame(MaterialityVerdict::Calculate, $row->materialityVerdict);
        $this->assertSame(62.5, $row->materialityScorePercent);
        $this->assertSame(50, $row->materialityThresholdPercent);
        $this->assertNull($row->materialityOverride);
    }

    public function testAnOverrideThatKeepsTheCategoryActiveIsExportedInItsActivityRows(): void
    {
        $user = UserMother::fromUsernameAndRole('agarcia', Role::Owner);
        $this->users->add($user);
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(1, 10.0)]);
        $footprint->includeSource(Scope::Scope3, Source::PurchasedGoodsAndServices);
        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, $user->id(), 'Exigencia del cliente');
        $material = $this->save(MaterialActivityMother::registered());
        $footprint->addActivity($material->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $row = $this->rowsByActivityId($this->createSUT()->build($footprint))[(string) $material->id()];

        $this->assertSame(MaterialityVerdict::Omit, $row->materialityVerdict);
        $this->assertStringStartsWith('agarcia · ', (string) $row->materialityOverride);
        $this->assertStringEndsWith(' · Exigencia del cliente', (string) $row->materialityOverride);
    }

    public function testAScope1ActivityRowCarriesNoMateriality(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::calculate(1)]);
        $combustible = $this->save(CombustibleActivityMother::registered());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $row = $this->rowsByActivityId($this->createSUT()->build($footprint))[(string) $combustible->id()];

        $this->assertNull($row->materialityVerdict);
        $this->assertNull($row->materialityScorePercent);
        $this->assertNull($row->materialityOverride);
    }

    public function testBuildsTheNonConsolidatedPropertyRowsAndThePropertyOfTheActivitiesOfACompanyFootprint(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndId('Hotel franquiciado', 'franchised-1'));
        $this->propertyRepository->add(PropertyMother::fromNameAndId('Local arrendado', 'leased-out-1'));
        $this->propertyRepository->add(PropertyMother::fromNameAndId('Oficina central', 'office-1'));
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, new Emissions('5000', '5000'));
        $footprint->recordNonConsolidatedProperty(new PropertyId('leased-out-1'), Source::DownstreamLeasedAssets, null, null, new Emissions('300'), '4500');
        $combustible = $this->save(CombustibleActivityMother::registered());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('office-1')));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(3, $rows);
        $this->assertSame('Oficina central', $rows[0]->propertyName);
        $this->assertSame(CarbonFootprintDetailRowType::NonConsolidatedProperty, $rows[1]->rowType);
        $this->assertSame(Source::DownstreamLeasedAssets, $rows[1]->source);
        $this->assertSame('Local arrendado', $rows[1]->propertyName);
        $this->assertSame('Local arrendado', $rows[1]->itemName);
        $this->assertSame('4500', $rows[1]->manualCo2eq);
        $this->assertSame(4500.0, (float) $rows[1]->emissionsCo2eq);
        $this->assertSame(Source::Franchises, $rows[2]->source);
        $this->assertSame('Hotel franquiciado', $rows[2]->propertyName);
        $this->assertNull($rows[2]->manualCo2eq);
        $this->assertSame(5000.0, (float) $rows[2]->emissionsCo2eq);
    }

    public function testBuildsTheExclusionRows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('P-1', (string) PropertyCarbonFootprintId::generate());
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NotApplicable, 'Sin equipos');
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'Sin datos');
        $electricity = $this->save(ElectricityActivityMother::registered());
        $footprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(3, $rows);
        $this->assertSame(CarbonFootprintDetailRowType::Activity, $rows[0]->rowType);
        $this->assertSame(Scope::Scope2, $rows[0]->stage);
        $this->assertSame(CarbonFootprintDetailRowType::Exclusion, $rows[1]->rowType);
        $this->assertSame(Scope::Scope1, $rows[1]->stage);
        $this->assertSame(Source::RefrigerantLeaks, $rows[1]->source);
        $this->assertSame(ExclusionReason::NotApplicable, $rows[1]->exclusionReason);
        $this->assertSame('Sin equipos', $rows[1]->exclusionComment);
        $this->assertSame(CarbonFootprintDetailRowType::Exclusion, $rows[2]->rowType);
        $this->assertSame(Scope::Scope3, $rows[2]->stage);
        $this->assertNull($rows[2]->source);
        $this->assertSame(ExclusionReason::NoDataAvailable, $rows[2]->exclusionReason);
        $this->assertSame('Sin datos', $rows[2]->exclusionComment);
    }

    public function testTheExclusionRowsOfTheScope3CarryTheirMaterialityDecision(): void
    {
        $user = UserMother::fromUsernameAndRole('agarcia', Role::Owner);
        $this->users->add($user);
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([
            MaterialityDecisionInputMother::omit(10, 0.0),
            MaterialityDecisionInputMother::calculate(1, 62.5, forcedByHighMagnitude: true),
        ]);
        $footprint->excludeSource(Scope::Scope3, Source::PurchasedGoodsAndServices, ExclusionReason::OutOfScope);
        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, $user->id(), 'Fuera del perímetro');
        $footprint->excludeSource(Scope::Scope3, Source::BusinessTravel, ExclusionReason::NoDataAvailable);
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NotApplicable);

        $rows = $this->exclusionRowsBySource($this->createSUT()->build($footprint));

        $recommended = $rows[Source::SoldProductsProcessing->value];
        $this->assertSame(0.0, $recommended->materialityScorePercent);
        $this->assertSame(50, $recommended->materialityThresholdPercent);
        $this->assertSame(MaterialityVerdict::Omit, $recommended->materialityVerdict);
        $this->assertFalse($recommended->materialityForcedByHighMagnitude);
        $this->assertNull($recommended->materialityOverride);

        $overridden = $rows[Source::PurchasedGoodsAndServices->value];
        $this->assertSame(MaterialityVerdict::Calculate, $overridden->materialityVerdict);
        $this->assertTrue($overridden->materialityForcedByHighMagnitude);
        $this->assertStringStartsWith('agarcia · ', (string) $overridden->materialityOverride);
        $this->assertStringEndsWith(' · Fuera del perímetro', (string) $overridden->materialityOverride);

        foreach ([Source::BusinessTravel, Source::RefrigerantLeaks] as $withoutDecision) {
            $this->assertNull($rows[$withoutDecision->value]->materialityVerdict);
            $this->assertNull($rows[$withoutDecision->value]->materialityScorePercent);
            $this->assertNull($rows[$withoutDecision->value]->materialityOverride);
        }
    }

    public function testAnOverrideByADeletedUserShowsAPlaceholder(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::calculate(1)]);
        $footprint->excludeSource(Scope::Scope3, Source::PurchasedGoodsAndServices, ExclusionReason::OutOfScope);
        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, UserId::generate(), 'Fuera del perímetro');

        $rows = $this->exclusionRowsBySource($this->createSUT()->build($footprint));

        $this->assertStringStartsWith('--- · ', (string) $rows[Source::PurchasedGoodsAndServices->value]->materialityOverride);
    }

    public function testTheWholeScope3ExclusionRowCarriesNoMaterialityDecision(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(10, 0.0)]);
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'Sin datos');

        $rows = array_values(array_filter($this->createSUT()->build($footprint), static fn(CarbonFootprintDetailRow $row): bool => CarbonFootprintDetailRowType::Exclusion === $row->rowType && Scope::Scope3 === $row->stage));

        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]->source);
        $this->assertNull($rows[0]->materialityVerdict);
        $this->assertNull($rows[0]->materialityScorePercent);
    }

    /**
     * @param list<CarbonFootprintDetailRow> $rows
     *
     * @return array<string, CarbonFootprintDetailRow>
     */
    private function exclusionRowsBySource(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (CarbonFootprintDetailRowType::Exclusion === $row->rowType && null !== $row->source) {
                $indexed[$row->source->value] = $row;
            }
        }

        return $indexed;
    }

    public function testBuildsThePhaseRowsOfAnEventFootprintWithoutDerivedRows(): void
    {
        $footprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $combustible = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $material = $this->save(MaterialActivityMother::registered());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Phase::Celebration, Source::FixedFacilities));
        $footprint->addActivity($material->id(), new ActivityPlacement(Phase::Materials, Source::Materials));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(2, $rows);
        $this->assertSame(Phase::Materials, $rows[0]->stage);
        $this->assertSame(Phase::Celebration, $rows[1]->stage);
        $this->assertSame(CarbonFootprintDetailRowType::Activity, $rows[1]->rowType);
    }

    public function testSkipsTheReferencedActivitiesThatDoNotExist(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
        $combustible = $this->save(CombustibleActivityMother::registered());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope1, Source::OwnVehicles));

        $rows = $this->createSUT()->build($footprint);

        $this->assertCount(1, $rows);
        $this->assertSame((string) $combustible->id(), $rows[0]->activityId);
    }

    public function testSortsByStageRowTypeSourcePropertyAndItemWithTheExclusionsLast(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndId('B', 'property-b'));
        $this->propertyRepository->add(PropertyMother::fromNameAndId('A', 'property-a'));
        $footprint = CompanyCarbonFootprintMother::from('C-1', (string) CompanyCarbonFootprintId::generate());
        $purchase = $this->save(ReportedEmissionsActivityMother::registered(description: 'Compras'));
        $fuelB = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $fuelA = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $electricity = $this->save(ElectricityActivityMother::registered());
        $refrigerantZ = $this->save(ReportedEmissionsActivityMother::registered(description: 'Z'));
        $refrigerantY = $this->save(ReportedEmissionsActivityMother::registered(description: 'Y'));
        $transport = $this->save(ReportedEmissionsActivityMother::registered(description: 'Transporte'));
        $footprint->addActivity($purchase->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $footprint->addActivity($fuelB->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('property-b')));
        $footprint->addActivity($fuelA->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('property-a')));
        $footprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->addActivity($refrigerantZ->id(), new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks));
        $footprint->addActivity($refrigerantY->id(), new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks));
        $footprint->addActivity($transport->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport));
        $footprint->excludeSource(Scope::Scope3, Source::Investments, ExclusionReason::NotApplicable);
        $footprint->excludeSource(Scope::Scope1, Source::OwnVehicles, ExclusionReason::NotApplicable);

        $rows = $this->createSUT()->build($footprint);

        $this->assertSame([
            'scope_1|fixed_facilities|activity|A',
            'scope_1|fixed_facilities|activity|B',
            'scope_1|refrigerant_leaks|activity|',
            'scope_1|refrigerant_leaks|activity|',
            'scope_2|building_electricity|activity|',
            'scope_3|purchased_goods_and_services|activity|',
            'scope_3|upstream_transport|activity|',
            'scope_3|fuel_and_energy_related|derived_fuel_and_energy|A',
            'scope_3|fuel_and_energy_related|derived_fuel_and_energy|B',
            'scope_1|own_vehicles|exclusion|',
            'scope_3|investments|exclusion|',
        ], array_map(static fn(CarbonFootprintDetailRow $row): string => implode('|', [$row->stage->key(), $row->source?->value, $row->rowType->value, $row->propertyName]), $rows));
        $this->assertSame('Y', $rows[2]->itemName);
        $this->assertSame('Z', $rows[3]->itemName);
    }

    public function testTheEmissionsOfTheRowsAddUpToTheTotalOfTheFootprint(): void
    {
        $footprintId = (string) CompanyCarbonFootprintId::generate();
        $this->propertyRepository->add(PropertyMother::fromNameAndId('Hotel franquiciado', 'franchised-1'));
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, new Emissions('5000', '5000'), '4500', id: $footprintId);
        $providerId = ProviderId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $providerId, sources: [Source::PurchasedGoodsAndServices], subjectType: CarbonFootprintReportSubjectType::Company);
        $contribution->ignore();
        $this->contributionRepository->add($contribution);
        $combustible = $this->save(CombustibleActivityMother::registeredWithWellToTank());
        $electricity = $this->save(ElectricityActivityMother::registered());
        $material = $this->save(MaterialActivityMother::registered());
        $ignored = $this->save(ReportedEmissionsActivityMother::registered());
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->addActivity($material->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $footprint->addActivity($ignored->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices, providerId: $providerId));
        $footprint->excludeSource(Scope::Scope3, Source::Investments, ExclusionReason::NotApplicable);
        $countedActivityReferences = CountedActivityReferencesMother::create($this->contributionRepository);
        $breakdownBuilder = new EmissionsBreakdownBuilder(
            $this->activityRepository,
            new DerivedSourceEmissionsCalculator($this->activityRepository, $countedActivityReferences),
            $countedActivityReferences,
        );

        $rows = $this->createSUT()->build($footprint);

        $this->assertSame(
            [
                CarbonFootprintDetailRowType::Activity,
                CarbonFootprintDetailRowType::Activity,
                CarbonFootprintDetailRowType::Activity,
                CarbonFootprintDetailRowType::DerivedFuelAndEnergy,
                CarbonFootprintDetailRowType::NonConsolidatedProperty,
                CarbonFootprintDetailRowType::Exclusion,
            ],
            array_map(static fn(CarbonFootprintDetailRow $row): CarbonFootprintDetailRowType => $row->rowType, $rows),
        );
        $this->assertEqualsWithDelta(
            (float) $breakdownBuilder->build($footprint, Scope::cases())->total->co2eq(),
            array_sum(array_map(static fn(CarbonFootprintDetailRow $row): float => (float) $row->emissionsCo2eq, $rows)),
            0.000001,
        );
    }

    public function testResolvesTheCurrentNamesOnEachBuild(): void
    {
        $this->propertyRepository->add(PropertyMother::fromNameAndId('Oficina', 'office-1'));
        $providerId = (string) ProviderId::generate();
        $provider = ProviderMother::fromNameAndId('Logística ACME', $providerId);
        $this->providerRepository->add($provider);
        $footprint = CompanyCarbonFootprintMother::from('C-1', (string) CompanyCarbonFootprintId::generate());
        $reported = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto'));
        $footprint->addActivity($reported->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id(), propertyId: new PropertyId('office-1')));
        $sut = $this->createSUT();

        $sut->build($footprint);

        $property = $this->propertyRepository->find(new PropertyId('office-1'));
        $this->assertNotNull($property);
        $this->propertyRepository->remove($property);
        $this->propertyRepository->add(PropertyMother::fromNameAndId('Sede central', 'office-1'));
        $this->providerRepository->remove($provider);
        $this->providerRepository->add(ProviderMother::fromNameAndId('Transportes Norte', $providerId));

        $rows = $sut->build($footprint);

        $this->assertSame('Sede central', $rows[0]->propertyName);
        $this->assertSame('Transportes Norte', $rows[0]->providerName);
    }

    public function testFillsTheTypeSpecificAttributesOfTheActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());
        $electricity = $this->save(ElectricityActivityMother::registeredWithLocationBasedResult());
        $transport = $this->save(TransportActivityMother::registeredInTonneKilometre());
        $waste = $this->save(WasteActivityMother::registered(containerCapacity: new Quantity(1100.0, Unit::Liter), fillPercentage: 80.0));
        $capitalGood = $this->save(CapitalGoodActivityMother::registered(acquisitionYear: 2023));
        $overnightStay = $this->save(OvernightStayActivityMother::registered(nights: 3, people: 2));
        $reported = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto 2025'));
        $footprint->addActivity($electricity->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->addActivity($transport->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport));
        $footprint->addActivity($waste->id(), new ActivityPlacement(Scope::Scope3, Source::OperationsWaste));
        $footprint->addActivity($capitalGood->id(), new ActivityPlacement(Scope::Scope3, Source::CapitalGoods));
        $footprint->addActivity($overnightStay->id(), new ActivityPlacement(Scope::Scope3, Source::BusinessTravel));
        $footprint->addActivity($reported->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));

        $rows = $this->rowsByActivityId($this->createSUT()->build($footprint));

        $this->assertSame(ElectricityActivityMother::locationBasedEmissions()->co2eq(), $rows[(string) $electricity->id()]->locationBasedCo2eq);
        $this->assertSame(2.5, $rows[(string) $transport->id()]->weightTonnes);
        $this->assertSame(350.0, $rows[(string) $transport->id()]->distanceKm);
        $this->assertSame(1100.0, $rows[(string) $waste->id()]->containerCapacityLitres);
        $this->assertSame(80.0, $rows[(string) $waste->id()]->fillPercentage);
        $this->assertSame(2023, $rows[(string) $capitalGood->id()]->acquisitionYear);
        $this->assertSame(3, $rows[(string) $overnightStay->id()]->nights);
        $this->assertSame(2, $rows[(string) $overnightStay->id()]->people);
        $this->assertSame('Reparto 2025', $rows[(string) $reported->id()]->dataSource);
        $this->assertSame('Reparto 2025', $rows[(string) $reported->id()]->itemName);
    }

    /**
     * @param list<CarbonFootprintDetailRow> $rows
     *
     * @return array<string, CarbonFootprintDetailRow>
     */
    private function rowsByActivityId(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (CarbonFootprintDetailRowType::Activity === $row->rowType && null !== $row->activityId) {
                $indexed[$row->activityId] = $row;
            }
        }

        return $indexed;
    }

    private function detailItemNameResolver(): ActivityItemNameResolver
    {
        return new ActivityItemNameResolver(
            new ActivityCatalogProviderRegistry([
                new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
                new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
                new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
                new MaterialCatalogProvider(MaterialRepositoryMother::inMemory()),
                new WasteCatalogProvider(WasteRepositoryMother::inMemory()),
                new CapitalGoodCatalogProvider(CapitalGoodRepositoryMother::inMemory()),
                new OvernightStayCatalogProvider(OvernightStayRepositoryMother::inMemory()),
            ]),
            new FakeTranslator(),
        );
    }

    private function createSUT(): CarbonFootprintDetailRowsBuilder
    {
        $countedActivityReferences = CountedActivityReferencesMother::create($this->contributionRepository);

        return new CarbonFootprintDetailRowsBuilder(
            $this->activityRepository,
            $countedActivityReferences,
            $this->detailItemNameResolver(),
            new DerivedSourceEmissionsCalculator($this->activityRepository, $countedActivityReferences),
            new ProviderNameResolver($this->providerRepository),
            $this->propertyRepository,
            $this->users,
        );
    }
}
