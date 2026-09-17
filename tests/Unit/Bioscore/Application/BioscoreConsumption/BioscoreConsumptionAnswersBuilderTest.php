<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreConsumption;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionAnswers;
use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionAnswersBuilder;
use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionSkipReason;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumption;
use App\Bioscore\Infrastructure\Persistence\InMemory\Repository\InMemoryBioscoreCatalogItemRepository;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RoadTransportAnswer;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCombustibleRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRefrigerantGasRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRoadTransportRepository;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepositoryMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionMother;
use App\Tests\Unit\Bioscore\Domain\Model\BioscoreConsumption\BioscorePropertyConsumptionsMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class BioscoreConsumptionAnswersBuilderTest extends TestCase
{
    private const PROPERTY_ID = 'property-1';

    private InMemoryBioscoreCatalogItemRepository $catalogItems;
    private InMemoryCombustibleRepository $combustibles;
    private InMemoryRoadTransportRepository $roadTransports;
    private InMemoryRefrigerantGasRepository $refrigerantGases;
    private InMemoryElectricityMarketerRepository $electricityMarketers;
    private string $combustibleId;
    private string $roadTransportId;
    private string $refrigerantGasId;
    private string $electricityMarketerId;
    private BioscoreConsumptionAnswersBuilder $sut;

    protected function setUp(): void
    {
        $this->catalogItems = BioscoreCatalogItemRepositoryMother::inMemory();
        $this->combustibles = new InMemoryCombustibleRepository();
        $this->roadTransports = new InMemoryRoadTransportRepository();
        $this->refrigerantGases = new InMemoryRefrigerantGasRepository();
        $this->electricityMarketers = new InMemoryElectricityMarketerRepository();

        $this->combustibleId = (string) Uuid::v7();
        $this->roadTransportId = (string) Uuid::v7();
        $this->refrigerantGasId = (string) Uuid::v7();
        $this->electricityMarketerId = (string) Uuid::v7();

        $this->combustibles->add(CombustibleMother::from('Gas natural', $this->combustibleId, Unit::CubicMeter));
        $this->roadTransports->add(RoadTransportMother::from('Gasolina', $this->roadTransportId, Unit::Liter));
        $this->refrigerantGases->add(RefrigerantGasMother::from('R-410A', $this->refrigerantGasId));
        $this->electricityMarketers->add(ElectricityMarketerMother::from('Endesa', $this->electricityMarketerId));

        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->combustibleId, catalog: BioscoreCatalog::FacilityFuel, bioscoreId: 7, name: 'Gas Natural'));
        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->roadTransportId, catalog: BioscoreCatalog::TransportFuel, bioscoreId: 8, name: 'Gasolina', unit: 'l'));
        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->refrigerantGasId, catalog: BioscoreCatalog::RefrigerantGas, bioscoreId: 9, name: 'R-410A', unit: 'kg'));
        $this->catalogItems->add(BioscoreCatalogItemMother::mapped($this->electricityMarketerId, catalog: BioscoreCatalog::ElectricityMarketer, bioscoreId: 3, name: 'Endesa', unit: 'kWh'));

        $this->sut = new BioscoreConsumptionAnswersBuilder(
            $this->catalogItems,
            new CatalogTargetFinder($this->combustibles, $this->roadTransports, $this->refrigerantGases, $this->electricityMarketers),
        );
    }

    public function testAFacilityFuelBecomesACombustibleAnswer(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0, 'm3')]);

        $this->assertSame([], $answers->skipped);
        $this->assertCount(1, $answers->sectionAnswers);
        $this->assertSame('combustibles', $answers->sectionAnswers[0]->definition->key);

        $answer = $answers->sectionAnswers[0]->answers[0];
        $this->assertInstanceOf(CombustibleAnswer::class, $answer);
        $this->assertSame($this->combustibleId, (string) $answer->combustibleId);
        $this->assertSame(1000.0, $answer->quantity->value());
        $this->assertSame(Unit::CubicMeter, $answer->quantity->unit());
        $this->assertSame('Bioscore', $answer->source);
        $this->assertSame(1, $answers->answerCount());
    }

    public function testADualUseFuelGoesToFacilities(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::dualUseFuel(7, 'Gas Natural', 500.0)]);

        $this->assertCount(1, $answers->sectionAnswers);
        $this->assertSame('combustibles', $answers->sectionAnswers[0]->definition->key);
        $this->assertInstanceOf(CombustibleAnswer::class, $answers->sectionAnswers[0]->answers[0]);
    }

    public function testATransportOnlyFuelBecomesARoadTransportAnswer(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::transportFuel(8, 'Gasolina', 200.0, 'l')]);

        $this->assertCount(1, $answers->sectionAnswers);
        $this->assertSame('vehicles', $answers->sectionAnswers[0]->definition->key);

        $answer = $answers->sectionAnswers[0]->answers[0];
        $this->assertInstanceOf(RoadTransportAnswer::class, $answer);
        $this->assertSame($this->roadTransportId, (string) $answer->roadTransportId);
        $this->assertSame(Unit::Liter, $answer->quantity->unit());
    }

    public function testAFuelWithoutUseFlagsIsSkippedAsUnmapped(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::fuelWithoutUse(7, 'Gas Natural', 10.0)]);

        $this->assertSame([], $answers->sectionAnswers);
        $this->assertCount(1, $answers->skipped);
        $this->assertSame(BioscoreConsumptionSkipReason::Unmapped, $answers->skipped[0]->reason);
        $this->assertSame(self::PROPERTY_ID, (string) $answers->skipped[0]->propertyId);
    }

    public function testARefrigerantGasBecomesARefrigerantGasAnswerInKilograms(): void
    {
        $answers = $this->build(Scope::Scope1, refrigerantGases: [BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0)]);

        $this->assertCount(1, $answers->sectionAnswers);
        $this->assertSame('refrigerantGases', $answers->sectionAnswers[0]->definition->key);

        $answer = $answers->sectionAnswers[0]->answers[0];
        $this->assertInstanceOf(RefrigerantGasAnswer::class, $answer);
        $this->assertSame($this->refrigerantGasId, (string) $answer->refrigerantGasId);
        $this->assertSame(Unit::Kilogram, $answer->quantity->unit());
    }

    public function testScope1IgnoresElectricityAndScope2OnlyUsesIt(): void
    {
        $electricity = [BioscoreConsumptionMother::electricity(3, 'Endesa', 10000.0)];
        $combustibles = [BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0)];

        $scope1 = $this->build(Scope::Scope1, electricity: $electricity, combustibles: $combustibles);
        $scope2 = $this->build(Scope::Scope2, electricity: $electricity, combustibles: $combustibles);

        $this->assertCount(1, $scope1->sectionAnswers);
        $this->assertSame('combustibles', $scope1->sectionAnswers[0]->definition->key);

        $this->assertCount(1, $scope2->sectionAnswers);
        $this->assertSame('electricity', $scope2->sectionAnswers[0]->definition->key);

        $answer = $scope2->sectionAnswers[0]->answers[0];
        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertSame($this->electricityMarketerId, (string) $answer->electricityMarketerId);
        $this->assertNull($answer->gdOFactorId);
        $this->assertSame(10000.0, $answer->quantity->value());
        $this->assertSame(Unit::KWh, $answer->quantity->unit());
    }

    public function testUnmappedIgnoredAndUnknownItemsAreSkippedAsUnmapped(): void
    {
        $this->catalogItems->add(BioscoreCatalogItemMother::registered(BioscoreCatalog::FacilityFuel, 10, 'Propano'));
        $this->catalogItems->add(BioscoreCatalogItemMother::ignored(BioscoreCatalog::FacilityFuel, 11, 'Butano'));

        $answers = $this->build(Scope::Scope1, combustibles: [
            BioscoreConsumptionMother::facilityFuel(10, 'Propano', 1.0),
            BioscoreConsumptionMother::facilityFuel(11, 'Butano', 2.0),
            BioscoreConsumptionMother::facilityFuel(12, 'Desconocido', 3.0),
        ]);

        $this->assertSame([], $answers->sectionAnswers);
        $this->assertCount(3, $answers->skipped);
        $this->assertSame([10, 11, 12], array_map(static fn($skipped): int => $skipped->bioscoreId, $answers->skipped));
        $this->assertSame(BioscoreConsumptionSkipReason::Unmapped, $answers->skipped[2]->reason);
    }

    public function testAnItemMappedToARemovedMasterIsSkippedAsTargetMissing(): void
    {
        $combustible = $this->combustibles->find(new CombustibleId($this->combustibleId));
        $this->assertNotNull($combustible);
        $combustible->remove();
        $this->combustibles->add($combustible);

        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0)]);

        $this->assertSame([], $answers->sectionAnswers);
        $this->assertCount(1, $answers->skipped);
        $this->assertSame(BioscoreConsumptionSkipReason::TargetMissing, $answers->skipped[0]->reason);
    }

    public function testAnEmptyUnitUsesTheMasterUnit(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0, '')]);

        $this->assertSame(Unit::CubicMeter, $answers->sectionAnswers[0]->answers[0]->quantity->unit());
    }

    public function testACompatibleUnitIsKept(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0, 'lt')]);

        $this->assertSame(Unit::Liter, $answers->sectionAnswers[0]->answers[0]->quantity->unit());
    }

    public function testAnIncompatibleUnitIsSkipped(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 1000.0, 'kg')]);

        $this->assertSame([], $answers->sectionAnswers);
        $this->assertCount(1, $answers->skipped);
        $this->assertSame(BioscoreConsumptionSkipReason::IncompatibleUnit, $answers->skipped[0]->reason);
        $this->assertSame('kg', $answers->skipped[0]->unit);
    }

    public function testAZeroConsumptionIsNeitherAnsweredNorSkipped(): void
    {
        $answers = $this->build(Scope::Scope1, combustibles: [
            BioscoreConsumptionMother::facilityFuel(7, 'Gas Natural', 0.0),
            BioscoreConsumptionMother::facilityFuel(12, 'Desconocido', 0.0),
        ]);

        $this->assertSame([], $answers->sectionAnswers);
        $this->assertSame([], $answers->skipped);
    }

    public function testSectionsWithoutAnswersAreNotReturned(): void
    {
        $answers = $this->build(Scope::Scope1, refrigerantGases: [BioscoreConsumptionMother::refrigerantGas(9, 'R-410A', 3.0)]);

        $this->assertCount(1, $answers->sectionAnswers);
        $this->assertSame('refrigerantGases', $answers->sectionAnswers[0]->definition->key);
    }

    /**
     * @param list<BioscoreConsumption> $electricity
     * @param list<BioscoreConsumption> $combustibles
     * @param list<BioscoreConsumption> $refrigerantGases
     */
    private function build(Scope $scope, array $electricity = [], array $combustibles = [], array $refrigerantGases = []): BioscoreConsumptionAnswers
    {
        return $this->sut->build($scope, BioscorePropertyConsumptionsMother::forProperty(
            self::PROPERTY_ID,
            electricity: $electricity,
            combustibles: $combustibles,
            refrigerantGases: $refrigerantGases,
        ));
    }
}
