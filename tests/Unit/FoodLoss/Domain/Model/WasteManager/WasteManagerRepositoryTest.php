<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\WasteManager;

use App\FoodLoss\Domain\Model\WasteManager\WasteManagerFilter;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class WasteManagerRepositoryTest extends KernelTestCase
{
    public function testAddWasteManagerToRepository(): void
    {
        $repository = $this->createSUT();

        $wasteManager = WasteManagerMother::registeredWasteManager();

        $this->assertNull($repository->find($wasteManager->id()));

        $repository->add($wasteManager);

        $recoverWasteManager = $repository->find($wasteManager->id());

        $this->assertNotNull($recoverWasteManager);
        $this->assertEquals($wasteManager->id(), $recoverWasteManager->id());
    }

    public function testRemoveWasteManagerToRepository(): void
    {
        $repository = $this->createSUT();

        $wasteManager = WasteManagerMother::registeredWasteManager();

        $repository->add($wasteManager);

        $recoverWasteManager = $repository->find($wasteManager->id());

        $this->assertNotNull($recoverWasteManager);
        $this->assertEquals($wasteManager->id(), $recoverWasteManager->id());

        $repository->remove($wasteManager);

        $this->assertNull($repository->find($wasteManager->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::fromName('Nombre específico'));

        $filter = new WasteManagerFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new WasteManagerFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::random());
        $repository->add(WasteManagerMother::fromName('Nombre específico'));

        $filter = new WasteManagerFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $wasteManager = WasteManagerMother::registeredWasteManager();

        $repository->add($wasteManager);

        $recoverWasteManager = $repository->find($wasteManager->id());

        $this->assertNotNull($recoverWasteManager);

        $recoverWasteManager->remove();

        $repository->add($wasteManager);

        $this->assertNull($repository->find($wasteManager->id()));
    }

    abstract protected function createSUT(): WasteManagerRepository;
}
