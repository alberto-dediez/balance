<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCombustibleRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryRefrigerantGasRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTransportRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class CatalogTargetFinderTest extends TestCase
{
    private InMemoryCombustibleRepository $combustibles;
    private InMemoryTransportRepository $transports;
    private InMemoryRefrigerantGasRepository $refrigerantGases;
    private InMemoryElectricityMarketerRepository $electricityMarketers;

    protected function setUp(): void
    {
        $this->combustibles = new InMemoryCombustibleRepository();
        $this->transports = new InMemoryTransportRepository();
        $this->refrigerantGases = new InMemoryRefrigerantGasRepository();
        $this->electricityMarketers = new InMemoryElectricityMarketerRepository();
    }

    public function testAllReturnsTheTargetsOfEachCatalog(): void
    {
        $this->combustibles->add(CombustibleMother::fromName('Gas natural'));
        $this->transports->add(TransportMother::fromName('Gasoil'));
        $this->refrigerantGases->add(RefrigerantGasMother::fromName('R-410A'));
        $this->electricityMarketers->add(ElectricityMarketerMother::fromName('Endesa'));

        $finder = $this->createSUT();

        $this->assertCount(1, $finder->all(BioscoreCatalog::FacilityFuel));
        $this->assertCount(1, $finder->all(BioscoreCatalog::TransportFuel));
        $this->assertCount(1, $finder->all(BioscoreCatalog::RefrigerantGas));
        $this->assertCount(1, $finder->all(BioscoreCatalog::ElectricityMarketer));
    }

    public function testVehicleIsOnlyFilledForTransportFuels(): void
    {
        $this->combustibles->add(CombustibleMother::fromName('Gas natural'));
        $this->transports->add(TransportMother::from('Gasoil', (string) Uuid::v7(), Unit::Liter, Vehicle::Vans));

        $finder = $this->createSUT();

        $this->assertNull($finder->all(BioscoreCatalog::FacilityFuel)[0]->vehicle);
        $this->assertSame(Vehicle::Vans, $finder->all(BioscoreCatalog::TransportFuel)[0]->vehicle);
    }

    public function testFindReturnsNullForARemovedTarget(): void
    {
        $combustible = CombustibleMother::fromName('Gas natural');
        $this->combustibles->add($combustible);

        $finder = $this->createSUT();

        $this->assertNotNull($finder->find(BioscoreCatalog::FacilityFuel, (string) $combustible->id()));

        $combustible->remove();
        $this->combustibles->add($combustible);

        $this->assertNull($finder->find(BioscoreCatalog::FacilityFuel, (string) $combustible->id()));
    }

    public function testFindReturnsNullForAnIdThatIsNotAUuid(): void
    {
        $this->assertNull($this->createSUT()->find(BioscoreCatalog::FacilityFuel, 'not-a-uuid'));
    }

    public function testMatchingIgnoresAccentsAndCase(): void
    {
        $this->combustibles->add(CombustibleMother::from('Gasóleo C', (string) Uuid::v7(), Unit::Liter));

        $candidates = $this->createSUT()->matching(BioscoreCatalog::FacilityFuel, ' gasoleo  c ', 'l');

        $this->assertCount(1, $candidates);
    }

    public function testMatchingUsesTranslations(): void
    {
        $combustible = CombustibleMother::from('Natural gas', (string) Uuid::v7(), Unit::CubicMeter);
        $combustible->translate('Gas natural', Locale::Spanish);
        $this->combustibles->add($combustible);

        $candidates = $this->createSUT()->matching(BioscoreCatalog::FacilityFuel, 'Gas Natural', 'm3');

        $this->assertCount(1, $candidates);
    }

    public function testMatchingExcludesIncompatibleUnits(): void
    {
        $this->combustibles->add(CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::KWh));

        $this->assertCount(0, $this->createSUT()->matching(BioscoreCatalog::FacilityFuel, 'Gas natural', 'm3'));
    }

    public function testMatchingAcceptsAnEmptyUnit(): void
    {
        $this->combustibles->add(CombustibleMother::from('Gas natural', (string) Uuid::v7(), Unit::KWh));

        $this->assertCount(1, $this->createSUT()->matching(BioscoreCatalog::FacilityFuel, 'Gas natural', ''));
    }

    public function testMatchingReturnsEveryCandidateWithTheSameName(): void
    {
        $this->transports->add(TransportMother::from('Gasoil', (string) Uuid::v7(), Unit::Liter, Vehicle::PassengerCars));
        $this->transports->add(TransportMother::from('Gasoil', (string) Uuid::v7(), Unit::Liter, Vehicle::Vans));

        $this->assertCount(2, $this->createSUT()->matching(BioscoreCatalog::TransportFuel, 'Gasoil', 'l'));
    }

    private function createSUT(): CatalogTargetFinder
    {
        return new CatalogTargetFinder(
            combustibleRepository: $this->combustibles,
            transportRepository: $this->transports,
            refrigerantGasRepository: $this->refrigerantGases,
            electricityMarketerRepository: $this->electricityMarketers,
        );
    }
}
