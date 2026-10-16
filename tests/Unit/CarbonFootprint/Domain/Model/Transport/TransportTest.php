<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportMode;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class TransportTest extends TestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testTranslateAddTranslation(): void
    {
        $transport = $this->transport();

        $this->assertCount(0, $transport->translations());
        $transport->translate('translated-name', Locale::English);
        $this->assertCount(1, $transport->translations());
    }

    public function testTranslateReturnOriginalNameIfNoLocaleIsSet(): void
    {
        $transport = $this->transport();
        $transport->translate('translated-name-en', Locale::English);
        $transport->translate('translated-name-it', Locale::Italian);
        $transport->translate('translated-name-ge', Locale::German);

        $this->assertEquals('transport-name', $transport->name());
    }

    public function testTranslateReturnTranslatedName(): void
    {
        $transport = $this->transport();
        $transport->translate('translated-name-en', Locale::English);
        $transport->translate('translated-name-it', Locale::Italian);
        $transport->translate('translated-name-ge', Locale::German);

        FakeLocaleSetter::set(Locale::Italian);

        $this->assertEquals('translated-name-it', $transport->name());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $transport = $this->transport();

        $transport->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $transport->emissionFactors());
        $this->assertSame('1.5', $transport->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $transport = $this->transport();

        $transport->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $transport->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $transport->emissionFactors());
        $this->assertSame('2.0', $transport->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $transport = $this->transport();

        $this->assertNull($transport->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $transport = $this->transport();
        $transport->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $transport->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $transport->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $transport->emissionFactors());
        $this->assertNull($transport->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $transport->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $transport->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $transport = $this->transport();

        $this->expectException(\InvalidArgumentException::class);

        $transport->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testUpdatingTransportUnitRestampsEmissionFactors(): void
    {
        $transport = $this->transport();
        $transport->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $transport->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertSame(Unit::Kilogram, $transport->emissionFactorFor(2023, $this->groupId())?->unit());

        $transport->update('transport-name', Unit::Piece, Vehicle::PassengerCars);

        $factor2023 = $transport->emissionFactorFor(2023, $this->groupId());
        $factor2024 = $transport->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor2023);
        $this->assertNotNull($factor2024);
        $this->assertSame(Unit::Piece, $factor2023->unit());
        $this->assertSame(Unit::Piece, $factor2024->unit());
        $this->assertSame('1.0', $factor2023->co2eq());
    }

    public function testUpdateChangesVehicle(): void
    {
        $transport = $this->transport();

        $this->assertSame(Vehicle::PassengerCars, $transport->vehicle());

        $transport->update('transport-name', Unit::Kilogram, Vehicle::Trucks);

        $this->assertSame(Vehicle::Trucks, $transport->vehicle());
    }

    public function testModeFollowsVehicleAfterUpdate(): void
    {
        $transport = $this->transport();

        $this->assertSame(TransportMode::Road, $transport->mode());

        $transport->update('transport-name', Unit::TonneKilometre, Vehicle::FreightTrain);

        $this->assertSame(TransportMode::Rail, $transport->mode());
    }

    public function testUpdatingVehicleDoesNotRestampEmissionFactors(): void
    {
        $transport = $this->transport();
        $transport->setEmissionFactor($this->emissionFactor(2024, '1.0'));

        $transport->update('transport-name', Unit::Kilogram, Vehicle::Trucks);

        $factor = $transport->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor);
        $this->assertSame(Unit::Kilogram, $factor->unit());
    }

    public function testRemoveEmissionFactor(): void
    {
        $transport = $this->transport();
        $transport->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $transport->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $transport->emissionFactors());
    }

    public function testEventsRebuildFromPayloadWithLegacyRoadTransportIdKey(): void
    {
        $transport = TransportMother::registeredTransport();
        $transport->update('transport-name-updated', Unit::Kilogram, Vehicle::PassengerCars);
        $transport->translate('translated-name', Locale::English);
        $transport->setEmissionFactor($this->emissionFactor(2024, '1.0'));
        $transport->setEmissionFactor($this->emissionFactor(2024, '2.0'));
        $transport->removeEmissionFactor(2024, $this->groupId());
        $transport->remove();

        $events = $transport->recordedEvents();
        $this->assertCount(7, $events);

        foreach ($events as $event) {
            $payload = $event->payload();
            $payload['roadTransportId'] = $payload['transportId'];
            unset($payload['transportId']);

            $rebuilt = $event::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $payload);

            $this->assertEquals($transport->id(), $rebuilt->aggregateRootId());
        }
    }

    private function transport(): Transport
    {
        return new Transport(
            id: TransportId::generate(),
            name: 'transport-name',
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
