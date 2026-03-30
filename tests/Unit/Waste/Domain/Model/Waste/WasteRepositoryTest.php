<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use App\Waste\Domain\Model\Waste\WasteFilter;
use App\Waste\Domain\Model\Waste\WasteRepository;
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

        $recoverWaste = $repository->find($waste->id());

        $this->assertNotNull($recoverWaste);
        $this->assertEquals($waste->id(), $recoverWaste->id());
    }

    public function testRemoveWasteToRepository(): void
    {
        $repository = $this->createSUT();

        $waste = WasteMother::registeredWaste();

        $repository->add($waste);

        $recoverWaste = $repository->find($waste->id());

        $this->assertNotNull($recoverWaste);
        $this->assertEquals($waste->id(), $recoverWaste->id());

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
        $repository->add(WasteMother::fromName('Plastico'));

        $filter = new WasteFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new WasteFilter(
            name: 'Plastico',
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
        $repository->add(WasteMother::fromName('Plastico'));

        $filter = new WasteFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new WasteFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $waste = WasteMother::registeredWaste();

        $repository->add($waste);

        $recoverWaste = $repository->find($waste->id());

        $this->assertNotNull($recoverWaste);

        $recoverWaste->remove();

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

        $recoverWaste = $repository->find($waste->id());

        $this->assertNotNull($recoverWaste);
        $this->assertEquals($waste->id(), $recoverWaste->id());

        $this->assertEquals('translated-name', $recoverWaste->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $waste = WasteMother::registeredWaste();

        $this->assertNull($repository->find($waste->id()));

        $waste->translate('translated-name', Locale::English);

        $repository->add($waste);

        $recoverWastes = $repository->findByFilter(new WasteFilter(name: 'translated-name'));

        $this->assertCount(1, $recoverWastes);
        $this->assertEquals($waste->id(), $recoverWastes[0]->id());

        $this->assertEquals('translated-name', $recoverWastes[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): WasteRepository;
}
