<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
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

        $repository->add(DeliveryLogMother::random());
        $repository->add(DeliveryLogMother::random());
        $repository->add(DeliveryLogMother::random());
        $repository->add(DeliveryLogMother::random());
        $repository->add(DeliveryLogMother::random());
        $repository->add(DeliveryLogMother::from(productType: 'Specific Product', id: '1', accountId: 'acc1'));

        $filter = new DeliveryLogFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new DeliveryLogFilter(
            productType: 'Specific Product',
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
