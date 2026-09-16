<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRegistered;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRemoved;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RefrigerantGasTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesTheEvent(): void
    {
        $refrigerantGas = new RefrigerantGas(id: RefrigerantGasId::generate());

        $refrigerantGas->register('R-410A');

        $this->assertInstanceOf(RefrigerantGasRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertSame('R-410A', $refrigerantGas->name());
    }

    public function testUnitIsAlwaysKilogram(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $this->assertSame(Unit::Kilogram, $refrigerantGas->unit());
    }

    public function testUpdatePublishesTheEvent(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $refrigerantGas->update('R-134a');

        $this->assertInstanceOf(RefrigerantGasUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertSame('R-134a', $refrigerantGas->name());
    }

    public function testRemovePublishesTheEvent(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $refrigerantGas->remove();

        $this->assertInstanceOf(RefrigerantGasRemoved::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($refrigerantGas->isRemoved());
    }

    public function testSetEmissionFactorAddsIt(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $refrigerantGas->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $this->assertCount(1, $refrigerantGas->emissionFactors());
        $this->assertSame('1.5', $refrigerantGas->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testSetEmissionFactorForSameYearReplacesIt(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $refrigerantGas->setEmissionFactor($this->emissionFactor(2024, '1.5'));
        $refrigerantGas->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $this->assertCount(1, $refrigerantGas->emissionFactors());
        $this->assertSame('2.0', $refrigerantGas->emissionFactorFor(2024, $this->groupId())?->co2eq());
    }

    public function testEmissionFactorForReturnsNullWhenMissing(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $this->assertNull($refrigerantGas->emissionFactorFor(1999, $this->groupId()));
    }

    public function testSetEmissionFactorUsesKilogramUnit(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $refrigerantGas->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $factor = $refrigerantGas->emissionFactorFor(2024, $this->groupId());

        $this->assertNotNull($factor);
        $this->assertSame(Unit::Kilogram, $factor->unit());
    }

    public function testSyncEmissionFactorsAddsUpdatesAndRemoves(): void
    {
        $refrigerantGas = $this->refrigerantGas();
        $refrigerantGas->setEmissionFactor($this->emissionFactor(2023, '1.0'));
        $refrigerantGas->setEmissionFactor($this->emissionFactor(2024, '2.0'));

        $refrigerantGas->syncEmissionFactors([
            $this->emissionFactor(2024, '2.5'),
            $this->emissionFactor(2025, '3.0'),
        ]);

        $this->assertCount(2, $refrigerantGas->emissionFactors());
        $this->assertNull($refrigerantGas->emissionFactorFor(2023, $this->groupId()));
        $this->assertSame('2.5', $refrigerantGas->emissionFactorFor(2024, $this->groupId())?->co2eq());
        $this->assertSame('3.0', $refrigerantGas->emissionFactorFor(2025, $this->groupId())?->co2eq());
    }

    public function testSyncEmissionFactorsThrowsOnDuplicateYear(): void
    {
        $refrigerantGas = $this->refrigerantGas();

        $this->expectException(\InvalidArgumentException::class);

        $refrigerantGas->syncEmissionFactors([
            $this->emissionFactor(2024, '1.0'),
            $this->emissionFactor(2024, '2.0'),
        ]);
    }

    public function testRemoveEmissionFactor(): void
    {
        $refrigerantGas = $this->refrigerantGas();
        $refrigerantGas->setEmissionFactor($this->emissionFactor(2024, '1.5'));

        $refrigerantGas->removeEmissionFactor(2024, $this->groupId());

        $this->assertCount(0, $refrigerantGas->emissionFactors());
    }

    private function refrigerantGas(): RefrigerantGas
    {
        return new RefrigerantGas(
            id: RefrigerantGasId::generate(),
            name: 'refrigerant-gas-name',
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
            unit: Unit::KWh,
            source: 'test-source',
        );
    }

    private ?EmissionFactorGroupId $groupId = null;

    private function groupId(): EmissionFactorGroupId
    {
        return $this->groupId ??= EmissionFactorGroupMother::default()->id();
    }
}
