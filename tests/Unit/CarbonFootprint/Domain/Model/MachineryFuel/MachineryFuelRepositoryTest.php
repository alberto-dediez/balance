<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MachineryFuelRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddMachineryFuelToRepository(): void
    {
        $repository = $this->createSUT();

        $machineryFuel = MachineryFuelMother::registeredMachineryFuel();

        $this->assertNull($repository->find($machineryFuel->id()));

        $repository->add($machineryFuel);

        $recovered = $repository->find($machineryFuel->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($machineryFuel->id(), $recovered->id());
    }

    public function testRemoveMachineryFuelToRepository(): void
    {
        $repository = $this->createSUT();

        $machineryFuel = MachineryFuelMother::registeredMachineryFuel();

        $repository->add($machineryFuel);

        $recovered = $repository->find($machineryFuel->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($machineryFuel->id(), $recovered->id());

        $repository->remove($machineryFuel);

        $this->assertNull($repository->find($machineryFuel->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::fromName('Nombre específico'));

        $filter = new MachineryFuelFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MachineryFuelFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithMachineryType(): void
    {
        $repository = $this->createSUT();

        $repository->add(MachineryFuelMother::from(
            name: 'Excavadora',
            id: (string) MachineryFuelId::generate(),
            machineryType: MachineryType::Agricultural,
        ));
        $repository->add(MachineryFuelMother::from(
            name: 'Motosierra',
            id: (string) MachineryFuelId::generate(),
            machineryType: MachineryType::Forestry,
        ));
        $repository->add(MachineryFuelMother::from(
            name: 'Generador',
            id: (string) MachineryFuelId::generate(),
            machineryType: MachineryType::CommercialInstitutionalIndustrial,
        ));

        $filter = new MachineryFuelFilter(machineryType: MachineryType::Forestry);

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertSame(MachineryType::Forestry, $result[0]->machineryType());
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::random());
        $repository->add(MachineryFuelMother::fromName('Nombre específico'));

        $filter = new MachineryFuelFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $machineryFuel = MachineryFuelMother::registeredMachineryFuel();

        $repository->add($machineryFuel);

        $recovered = $repository->find($machineryFuel->id());

        $this->assertNotNull($recovered);

        $recovered->remove();

        $repository->add($machineryFuel);

        $this->assertNull($repository->find($machineryFuel->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $machineryFuel = MachineryFuelMother::registeredMachineryFuel();

        $this->assertNull($repository->find($machineryFuel->id()));

        $machineryFuel->translate('translated-name', Locale::English);

        $repository->add($machineryFuel);

        $recovered = $repository->find($machineryFuel->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($machineryFuel->id(), $recovered->id());
        $this->assertEquals('translated-name', $recovered->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $machineryFuel = MachineryFuelMother::registeredMachineryFuel();

        $machineryFuel->translate('translated-name', Locale::English);

        $repository->add($machineryFuel);

        $recovered = $repository->findByFilter(new MachineryFuelFilter(name: 'translated-name'));

        $this->assertCount(1, $recovered);
        $this->assertEquals($machineryFuel->id(), $recovered[0]->id());
        $this->assertEquals('translated-name', $recovered[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MachineryFuelRepository;
}
