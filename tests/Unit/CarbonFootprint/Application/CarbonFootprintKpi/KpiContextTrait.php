<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Application\CarbonFootprintKpi\GasBreakdownCalculator;
use App\CarbonFootprint\Application\CarbonFootprintKpi\InventoryResultsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmissionFactorGroupRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data\OrganizationalCollectorContextTrait;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;

trait KpiContextTrait
{
    use OrganizationalCollectorContextTrait;

    private InMemoryEmissionFactorGroupRepository $emissionFactorGroups;

    private function initKpiContext(): void
    {
        $this->initActivities();
        $this->emissionFactorGroups = EmissionFactorGroupRepositoryMother::inMemory();
        $this->emissionFactorGroups->add(EmissionFactorGroupMother::default());
    }

    private function gasBreakdownCalculator(): GasBreakdownCalculator
    {
        return new GasBreakdownCalculator($this->activityRepository, CountedActivityReferencesMother::create());
    }

    private function inventoryResultsBuilder(InMemoryPropertyRepository $properties): InventoryResultsBuilder
    {
        return new InventoryResultsBuilder(
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            $this->derivedSourceEmissionsCalculator(),
            $this->gasBreakdownCalculator(),
            $this->emissionFactorGroups,
            new CompanyProperties($properties),
            new FakeTranslator(),
        );
    }

    private function combustibleActivityWithGases(string $co2eq, string $co2, string $ch4Grams, string $n2oGrams, float $quantity = 100.0, string $factor = '1.8'): Activity
    {
        return $this->save(CombustibleActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            combustibleId: CombustibleId::generate(),
            emissionFactor: new EmissionFactor($factor, $factor, '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::Liter, 'MITECO'),
            emissions: new Emissions($co2eq, $co2, $ch4Grams, $n2oGrams),
            quantity: new Quantity($quantity, Unit::Liter),
        ));
    }

    private function refrigerantActivity(string $co2eq): Activity
    {
        return $this->save(RefrigerantGasActivity::register(
            id: ActivityId::generate(),
            accountId: new AccountId('account-id'),
            refrigerantGasId: RefrigerantGasId::generate(),
            emissionFactor: new EmissionFactor('2088', '2088', '0', '0', 2025, EmissionFactorGroupMother::defaultId(), Unit::Kilogram, 'IPCC'),
            emissions: new Emissions($co2eq, $co2eq, '0', '0'),
            quantity: new Quantity(1.0, Unit::Kilogram),
        ));
    }
}
