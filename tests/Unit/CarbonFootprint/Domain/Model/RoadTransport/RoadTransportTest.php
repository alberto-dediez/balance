<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RoadTransportTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $roadTransport = $this->roadTransport();

        $this->assertCount(0, $roadTransport->translations());
        $roadTransport->translate('translated-name', Locale::English);
        $this->assertCount(1, $roadTransport->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $roadTransport = $this->roadTransport();
        $roadTransport->translate('translated-name-en', Locale::English);
        $roadTransport->translate('translated-name-it', Locale::Italian);
        $roadTransport->translate('translated-name-ge', Locale::German);

        $this->assertEquals('road-transport-name', $roadTransport->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $roadTransport = $this->roadTransport();
        $roadTransport->translate('translated-name-en', Locale::English);
        $roadTransport->translate('translated-name-it', Locale::Italian);
        $roadTransport->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $roadTransport->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $roadTransport = $this->roadTransport();

        $roadTransport->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $roadTransport->emissionFactors());
        $this->assertSame('1.5', $roadTransport->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $roadTransport = $this->roadTransport();

        $roadTransport->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $roadTransport->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $roadTransport->emissionFactors());
        $this->assertSame('2.0', $roadTransport->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $roadTransport = $this->roadTransport();

        $this->assertNull($roadTransport->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $roadTransport = $this->roadTransport();
        $roadTransport->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $roadTransport->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $roadTransport->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $roadTransport->emissionFactors());
        $this->assertNull($roadTransport->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $roadTransport->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $roadTransport->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $roadTransport = $this->roadTransport();

        $this->expectException(\InvalidArgumentException::class);

        $roadTransport->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingRoadTransportUnitRestampsEmissionFactors(): void
    {
        $roadTransport = $this->roadTransport();
        $roadTransport->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $roadTransport->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $roadTransport->emissionFactorFor(2023, $this->groupId())?->unit());

        $roadTransport->update('road-transport-name', Unit::Piece, Vehicle::PassengerCars);

        $factor2023 = $roadTransport->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $roadTransport->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        // Other fields are preserved.
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testUpdateChangesVehicle(): void
    {
        $roadTransport = $this->roadTransport();

        $this->assertSame(Vehicle::PassengerCars, $roadTransport->vehicle());

        $roadTransport->update('road-transport-name', Unit::Kilogram, Vehicle::Trucks);

        $this->assertSame(Vehicle::Trucks, $roadTransport->vehicle());
    }

    public function testUpdatingVehicleDoesNotRestampEmissionFactors(): void
    {
        $roadTransport = $this->roadTransport();
        $roadTransport->setEmissionFactor($this->emissionFactor(2024, '1.0'));

        $roadTransport->update('road-transport-name', Unit::Kilogram, Vehicle::Trucks);

        $factor = $roadTransport->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor);
        $this->assertSame(Unit::Kilogram, $factor->unit());
    }

    public function testRemoveEmissionFactor(): void
    {
        $roadTransport = $this->roadTransport();
        $roadTransport->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $roadTransport->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $roadTransport->emissionFactors());
    }

    private function roadTransport(): RoadTransport
    {
        return new RoadTransport(
            id: RoadTransportId::generate(),
            name: 'road-transport-name',
            unit: Unit::Kilogram,
            vehicle: Vehicle::PassengerCars,
        );
    }

    private function emissionFactor(int $year, string $co2eq): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: $co2eq,
            co2: '0.1',
            ch4: '0.2',
            n2o: '0.3',
            year: $year,
            emissionFactorGroupId: $this->groupId(),
            unit: Unit::Kilogram,
            source: 'test-source',
        );
    }

    private ?EmissionFactorGroupId $groupId = null;

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::default()->id();
    }
}
