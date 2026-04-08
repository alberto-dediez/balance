<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Event\Property\RemoveDoggyBagSignsOnPropertyRemovedSubscriber;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use PHPUnit\Framework\TestCase;

class RemoveDoggyBagSignsOnPropertyRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToPropertyRemoved(): void
    {
        $sut = new RemoveDoggyBagSignsOnPropertyRemovedSubscriber(DoggyBagSignRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new PropertyRemoved(PropertyId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = new RemoveDoggyBagSignsOnPropertyRemovedSubscriber(DoggyBagSignRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new DoggyBagSignRemoved(DoggyBagSignId::generate())));
    }

    public function testHandleRemovesDoggyBagSignsAssociatedToProperty(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();
        $propertyId = PropertyId::generate();
        $otherPropertyId = PropertyId::generate();

        $dbs1 = DoggyBagSignMother::withPropertyId($propertyId);
        $dbs2 = DoggyBagSignMother::withPropertyId($propertyId);
        $dbs3 = DoggyBagSignMother::withPropertyId($otherPropertyId);

        $repository->add($dbs1);
        $repository->add($dbs2);
        $repository->add($dbs3);

        $sut = new RemoveDoggyBagSignsOnPropertyRemovedSubscriber($repository);
        $sut->handle(new PropertyRemoved($propertyId));

        $updatedDbs1 = $repository->find($dbs1->id());
        $updatedDbs2 = $repository->find($dbs2->id());
        $updatedDbs3 = $repository->find($dbs3->id());

        $this->assertNull($updatedDbs1);
        $this->assertNull($updatedDbs2);
        $this->assertNotNull($updatedDbs3);
        $this->assertFalse($updatedDbs3->isRemoved());
    }

    public function testHandleDoesNothingIfNoAssociatedDoggyBagSigns(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();

        $dbs = DoggyBagSignMother::random();
        $repository->add($dbs);

        $sut = new RemoveDoggyBagSignsOnPropertyRemovedSubscriber($repository);
        $sut->handle(new PropertyRemoved(PropertyId::generate()));

        $this->assertNotNull($repository->find($dbs->id()));
        $this->assertFalse($repository->find($dbs->id())->isRemoved());
    }
}
