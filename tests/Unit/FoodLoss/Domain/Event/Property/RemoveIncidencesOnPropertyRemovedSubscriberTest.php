<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Event\Property\RemoveIncidencesOnPropertyRemovedSubscriber;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Incidence\IncidenceRepositoryMother;
use PHPUnit\Framework\TestCase;

class RemoveIncidencesOnPropertyRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToPropertyRemoved(): void
    {
        $sut = new RemoveIncidencesOnPropertyRemovedSubscriber(IncidenceRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new PropertyRemoved(PropertyId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = new RemoveIncidencesOnPropertyRemovedSubscriber(IncidenceRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new IncidenceRemoved(IncidenceId::generate())));
    }

    public function testHandleRemovesIncidencesAssociatedToProperty(): void
    {
        $repository = IncidenceRepositoryMother::inMemory();
        $propertyId = PropertyId::generate();
        $otherPropertyId = PropertyId::generate();

        $inc1 = IncidenceMother::withPropertyId($propertyId);
        $inc2 = IncidenceMother::withPropertyId($propertyId);
        $inc3 = IncidenceMother::withPropertyId($otherPropertyId);

        $repository->add($inc1);
        $repository->add($inc2);
        $repository->add($inc3);

        $sut = new RemoveIncidencesOnPropertyRemovedSubscriber($repository);
        $sut->handle(new PropertyRemoved($propertyId));

        $updatedInc1 = $repository->find($inc1->id());
        $updatedInc2 = $repository->find($inc2->id());
        $updatedInc3 = $repository->find($inc3->id());

        $this->assertNull($updatedInc1);
        $this->assertNull($updatedInc2);
        $this->assertNotNull($updatedInc3);
        $this->assertFalse($updatedInc3->isRemoved());
    }

    public function testHandleDoesNothingIfNoAssociatedIncidences(): void
    {
        $repository = IncidenceRepositoryMother::inMemory();

        $inc = IncidenceMother::random();
        $repository->add($inc);

        $sut = new RemoveIncidencesOnPropertyRemovedSubscriber($repository);
        $sut->handle(new PropertyRemoved(PropertyId::generate()));

        $this->assertNotNull($repository->find($inc->id()));
        $this->assertFalse($repository->find($inc->id())->isRemoved());
    }
}
