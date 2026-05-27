<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\FoodType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DeliveryLogRepositoryTest extends KernelTestCase
{
    public function testAddDeliveryLogToRepository(): void
    {
        $repository = $this->createSUT();

        $deliveryLog = DeliveryLogMother::registeredDeliveryLog();

        $this->assertNull($repository->find($deliveryLog->id()));

        $repository->add($deliveryLog);

        $recoverDeliveryLog = $repository->find($deliveryLog->id());

        $this->assertNotNull($recoverDeliveryLog);
        $this->assertEquals($deliveryLog->id(), $recoverDeliveryLog->id());
    }

    public function testRemoveDeliveryLogToRepository(): void
    {
        $repository = $this->createSUT();

        $deliveryLog = DeliveryLogMother::registeredDeliveryLog();

        $repository->add($deliveryLog);

        $recoverDeliveryLog = $repository->find($deliveryLog->id());

        $this->assertNotNull($recoverDeliveryLog);
        $this->assertEquals($deliveryLog->id(), $recoverDeliveryLog->id());

        $repository->remove($deliveryLog);

        $this->assertNull($repository->find($deliveryLog->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(DeliveryLogMother::from(productType: FoodType::Fish, id: '2', accountId: 'acc2'));
        $repository->add(DeliveryLogMother::from(productType: FoodType::Fruit, id: '3', accountId: 'acc3'));
        $repository->add(DeliveryLogMother::from(productType: FoodType::Eggs, id: '4', accountId: 'acc4'));
        $repository->add(DeliveryLogMother::from(productType: FoodType::Others, id: '5', accountId: 'acc5'));
        $repository->add(DeliveryLogMother::from(productType: FoodType::Vegetables, id: '6', accountId: 'acc6'));
        $repository->add(DeliveryLogMother::from(productType: FoodType::Meat, id: '1', accountId: 'acc1'));

        $filter = new DeliveryLogFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new DeliveryLogFilter(
            productType: FoodType::Meat,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $deliveryLog = DeliveryLogMother::registeredDeliveryLog();

        $repository->add($deliveryLog);

        $recoverDeliveryLog = $repository->find($deliveryLog->id());

        $this->assertNotNull($recoverDeliveryLog);

        $recoverDeliveryLog->remove();

        $repository->add($deliveryLog);

        $this->assertNull($repository->find($deliveryLog->id()));
    }

    abstract protected function createSUT(): DeliveryLogRepository;
}
