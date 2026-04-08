<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\CriticalPoint;

use App\FoodLoss\Domain\Event\CriticalPoint\RemoveDoggyBagExceptionsOnCriticalPointRemovedSubscriber;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRemoved;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepositoryMother;
use PHPUnit\Framework\TestCase;

class RemoveDoggyBagExceptionsOnCriticalPointRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToCriticalPointRemoved(): void
    {
        $sut = new RemoveDoggyBagExceptionsOnCriticalPointRemovedSubscriber(DoggyBagExceptionRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new CriticalPointRemoved(CriticalPointId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = new RemoveDoggyBagExceptionsOnCriticalPointRemovedSubscriber(DoggyBagExceptionRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new DoggyBagExceptionRemoved(DoggyBagExceptionId::generate())));
    }

    public function testHandleRemovesDoggyBagExceptionsAssociatedToCriticalPoint(): void
    {
        $repository = DoggyBagExceptionRepositoryMother::inMemory();
        $criticalPointId = CriticalPointId::generate();
        $otherCriticalPointId = CriticalPointId::generate();

        $dbe1 = DoggyBagExceptionMother::withCriticalPointId($criticalPointId);
        $dbe2 = DoggyBagExceptionMother::withCriticalPointId($criticalPointId);
        $dbe3 = DoggyBagExceptionMother::withCriticalPointId($otherCriticalPointId);

        $repository->add($dbe1);
        $repository->add($dbe2);
        $repository->add($dbe3);

        $sut = new RemoveDoggyBagExceptionsOnCriticalPointRemovedSubscriber($repository);
        $sut->handle(new CriticalPointRemoved($criticalPointId));

        $updatedDbe1 = $repository->find($dbe1->id());
        $updatedDbe2 = $repository->find($dbe2->id());
        $updatedDbe3 = $repository->find($dbe3->id());

        // Since it's an InMemory repository, find() should return null if isRemoved() is true
        // and findByFilter() should also not return it if not specified.
        $this->assertNull($updatedDbe1);
        $this->assertNull($updatedDbe2);
        $this->assertNotNull($updatedDbe3);
        $this->assertFalse($updatedDbe3->isRemoved());
    }

    public function testHandleDoesNothingIfNoAssociatedDoggyBagExceptions(): void
    {
        $repository = DoggyBagExceptionRepositoryMother::inMemory();

        $dbe = DoggyBagExceptionMother::random();
        $repository->add($dbe);

        $sut = new RemoveDoggyBagExceptionsOnCriticalPointRemovedSubscriber($repository);
        $sut->handle(new CriticalPointRemoved(CriticalPointId::generate()));

        $this->assertNotNull($repository->find($dbe->id()));
        $this->assertFalse($repository->find($dbe->id())->isRemoved());
    }
}
