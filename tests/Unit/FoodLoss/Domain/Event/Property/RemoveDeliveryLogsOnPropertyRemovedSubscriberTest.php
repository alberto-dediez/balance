<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Event\Property\RemoveDeliveryLogsOnPropertyRemovedSubscriber;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use PHPUnit\Framework\TestCase;

class RemoveDeliveryLogsOnPropertyRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToPropertyRemoved(): void
    {
        $sut = new RemoveDeliveryLogsOnPropertyRemovedSubscriber(DeliveryLogRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new PropertyRemoved(PropertyId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = new RemoveDeliveryLogsOnPropertyRemovedSubscriber(DeliveryLogRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new DeliveryLogRemoved(DeliveryLogId::generate())));
    }

    public function testHandleRemovesDeliveryLogsAssociatedToProperty(): void
    {
        $repository = DeliveryLogRepositoryMother::inMemory();
        $propertyId = PropertyId::generate();
        $otherPropertyId = PropertyId::generate();

        $dl1 = DeliveryLogMother::withPropertyId($propertyId);
        $dl2 = DeliveryLogMother::withPropertyId($propertyId);
        $dl3 = DeliveryLogMother::withPropertyId($otherPropertyId);

        $repository->add($dl1);
        $repository->add($dl2);
        $repository->add($dl3);

        $sut = new RemoveDeliveryLogsOnPropertyRemovedSubscriber($repository);
        $sut->handle(new PropertyRemoved($propertyId));

        $updatedDl1 = $repository->find($dl1->id());
        $updatedDl2 = $repository->find($dl2->id());
        $updatedDl3 = $repository->find($dl3->id());

        $this->assertNull($updatedDl1);
        $this->assertNull($updatedDl2);
        $this->assertNotNull($updatedDl3);
        $this->assertFalse($updatedDl3->isRemoved());
    }

    public function testHandleDoesNothingIfNoAssociatedDeliveryLogs(): void
    {
        $repository = DeliveryLogRepositoryMother::inMemory();

        $dl = DeliveryLogMother::random();
        $repository->add($dl);

        $sut = new RemoveDeliveryLogsOnPropertyRemovedSubscriber($repository);
        $sut->handle(new PropertyRemoved(PropertyId::generate()));

        $this->assertNotNull($repository->find($dl->id()));
        $this->assertFalse($repository->find($dl->id())->isRemoved());
    }
}
