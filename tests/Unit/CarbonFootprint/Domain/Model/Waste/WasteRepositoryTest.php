<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Waste;

use App\CarbonFootprint\Domain\Model\Waste\WasteFilter;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class WasteRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddWasteToRepository(): void
    {
        $repository = $this->createSUT();

        $waste = WasteMother::registeredWaste();

        $this->assertNull($repository->find($waste->id()));

        $repository->add($waste);

        $recoveredWaste = $repository->find($waste->id());

        $this->assertNotNull($recoveredWaste);
        $this->assertEquals($waste->id(), $recoveredWaste->id());
    }

    public function testRemoveWasteToRepository(): void
    {
        $repository = $this->createSUT();

        $waste = WasteMother::registeredWaste();

        $repository->add($waste);

        $recoveredWaste = $repository->find($waste->id());

        $this->assertNotNull($recoveredWaste);
        $this->assertEquals($waste->id(), $recoveredWaste->id());

        $repository->remove($waste);

        $this->assertNull($repository->find($waste->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::fromName('Nombre específico'));

        $filter = new WasteFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new WasteFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::fromName('Nombre específico'));

        $filter = new WasteFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $waste = WasteMother::registeredWaste();

        $repository->add($waste);

        $recoveredWaste = $repository->find($waste->id());

        $this->assertNotNull($recoveredWaste);

        $recoveredWaste->remove();

        $repository->add($waste);

        $this->assertNull($repository->find($waste->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $waste = WasteMother::registeredWaste();

        $this->assertNull($repository->find($waste->id()));

        $waste->translate('translated-name', Locale::English);

        $repository->add($waste);

        $recoveredWaste = $repository->find($waste->id());

        $this->assertNotNull($recoveredWaste);
        $this->assertEquals($waste->id(), $recoveredWaste->id());
        $this->assertEquals('translated-name', $recoveredWaste->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $waste = WasteMother::registeredWaste();

        $waste->translate('translated-name', Locale::English);

        $repository->add($waste);

        $recoveredWastes = $repository->findByFilter(new WasteFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredWastes);
        $this->assertEquals($waste->id(), $recoveredWastes[0]->id());
        $this->assertEquals('translated-name', $recoveredWastes[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): WasteRepository;
}
