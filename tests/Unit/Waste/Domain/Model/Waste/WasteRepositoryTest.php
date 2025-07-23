<?php

namespace App\Tests\Unit\Waste\Domain\Model\Waste;

use App\Waste\Domain\Model\Waste\WasteFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Waste\Domain\Model\Waste\WasteRepository;

abstract class WasteRepositoryTest extends KernelTestCase
{
    public function testAddWasteToRepository()
    {   
        $repository = $this->createSUT();

        $waste = WasteMother::registeredWaste();

        $this->assertNull($repository->find($waste->id()));

        $repository->add($waste);

        $recoverWaste = $repository->find($waste->id());

        $this->assertNotNull($recoverWaste);
        $this->assertEquals($waste->id(), $recoverWaste->id());
    }

    public function testRemoveWasteToRepository()
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

    public function testFindByName()
    {
        $repository = $this->createSUT();

        $waste = WasteMother::registeredWaste();

        $this->assertNull($repository->findByName($waste->name()));

        $repository->add($waste);

        $recoverWaste = $repository->findByName($waste->name());

        $this->assertNotNull($recoverWaste);
        $this->assertEquals($waste->id(), $recoverWaste->id());
    }

    public function testFindByFilter()
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
            name: 'Plastico'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
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
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved()
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

    abstract protected function createSUT(): WasteRepository;
}
