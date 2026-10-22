<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\TransportCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ActivityItemNameResolver;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\MitigationPlanReportBuilder;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\OrganizationalCarbonFootprintReportLinesBuilder;
use App\CarbonFootprint\Application\MitigationAction\MitigationEmissionSourcesBuilder;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationActionRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;

trait OrganizationalCollectorContextTrait
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryMitigationActionRepository $mitigationActionRepository;

    private function initActivities(): void
    {
        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->mitigationActionRepository = new InMemoryMitigationActionRepository();
    }

    private function mitigationPlanBuilder(): MitigationPlanReportBuilder
    {
        return new MitigationPlanReportBuilder(
            $this->mitigationActionRepository,
            new MitigationEmissionSourcesBuilder($this->activityRepository, $this->itemNameResolver(), $this->derivedSourceEmissionsCalculator()),
        );
    }

    private function breakdownBuilder(): EmissionsBreakdownBuilder
    {
        return new EmissionsBreakdownBuilder($this->activityRepository, $this->derivedSourceEmissionsCalculator());
    }

    private function derivedSourceEmissionsCalculator(): DerivedSourceEmissionsCalculator
    {
        return new DerivedSourceEmissionsCalculator($this->activityRepository);
    }

    private function linesBuilder(): OrganizationalCarbonFootprintReportLinesBuilder
    {
        return new OrganizationalCarbonFootprintReportLinesBuilder(
            $this->activityRepository,
            $this->itemNameResolver(),
        );
    }

    private function itemNameResolver(): ActivityItemNameResolver
    {
        return new ActivityItemNameResolver(
            new ActivityCatalogProviderRegistry([
                new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
                new TransportCatalogProvider(TransportRepositoryMother::inMemory()),
                new ElectricityCatalogProvider(ElectricityMarketerRepositoryMother::inMemory(), GdOFactorRepositoryMother::inMemory()),
            ]),
            new FakeTranslator(),
        );
    }

    private function combustibleActivity(string $co2eq): Activity
    {
        return $this->save(CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            emissionFactor: new EmissionFactor('1.8', '1.8', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::Liter, 'MITECO'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(100.0, Unit::Liter),
        ));
    }

    private function combustibleActivityWithWellToTank(string $co2eq, string $wellToTank): Activity
    {
        return $this->save(CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            emissionFactor: new EmissionFactor('1.8', '1.8', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::Liter, 'MITECO'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(100.0, Unit::Liter),
            wellToTankEmissionFactor: new EmissionFactor('0.6', '0.6', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::Liter, 'DEFRA'),
            wellToTankEmissions: new Emissions($wellToTank, $wellToTank, '0', '0'),
        ));
    }

    private function transportActivity(string $co2eq): Activity
    {
        return $this->save(TransportActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            transportId: TransportId::generate(),
            emissionFactor: new EmissionFactor('0.25', '0.25', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::Kilometer, 'MITECO'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(5000.0, Unit::Kilometer),
        ));
    }

    private function electricityActivity(string $co2eq, ?string $locationBasedCo2eq): Activity
    {
        return $this->save(ElectricityActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            electricityMarketerId: ElectricityMarketerId::generate(),
            gdOFactorId: null,
            emissionFactor: new EmissionFactor('0.25', '0.25', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'Comercializadora'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(10000.0, Unit::KWh),
            electricityMixId: null !== $locationBasedCo2eq ? ElectricityMixId::generate() : null,
            locationBasedEmissionFactor: null !== $locationBasedCo2eq ? new EmissionFactor('0.2', '0.2', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'REE') : null,
            locationBasedEmissions: null !== $locationBasedCo2eq ? new Emissions($locationBasedCo2eq, $locationBasedCo2eq, '0', '0') : null,
            locationBasedWellToTankEmissionFactor: null !== $locationBasedCo2eq ? EmissionFactor::zeroFor(2025, EmissionFactorGroupMother::defaultId(), Unit::KWh) : null,
            locationBasedWellToTankEmissions: null !== $locationBasedCo2eq ? new Emissions() : null,
        ));
    }

    private function save(Activity $activity): Activity
    {
        $this->activityRepository->save($activity);

        return $activity;
    }
}
