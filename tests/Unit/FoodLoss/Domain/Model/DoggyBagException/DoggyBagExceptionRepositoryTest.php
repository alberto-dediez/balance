<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagException;

use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionFilter;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DoggyBagExceptionRepositoryTest extends KernelTestCase
{
    public function testAddDoggyBagExceptionToRepository(): void
    {
        $repository = $this->createSUT();

        $doggyBagException = DoggyBagExceptionMother::registeredDoggyBagException();

        $this->assertNull($repository->find($doggyBagException->id()));

        $repository->add($doggyBagException);

        $recoverDoggyBagException = $repository->find($doggyBagException->id());

        $this->assertNotNull($recoverDoggyBagException);
        $this->assertEquals($doggyBagException->id(), $recoverDoggyBagException->id());
    }

    public function testRemoveDoggyBagExceptionToRepository(): void
    {
        $repository = $this->createSUT();

        $doggyBagException = DoggyBagExceptionMother::registeredDoggyBagException();

        $repository->add($doggyBagException);

        $recoverDoggyBagException = $repository->find($doggyBagException->id());

        $this->assertNotNull($recoverDoggyBagException);
        $this->assertEquals($doggyBagException->id(), $recoverDoggyBagException->id());

        $repository->remove($doggyBagException);

        $this->assertNull($repository->find($doggyBagException->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(DoggyBagExceptionMother::random());
        $repository->add(DoggyBagExceptionMother::random());
        $repository->add(DoggyBagExceptionMother::random());
        $repository->add(DoggyBagExceptionMother::random());
        $repository->add(DoggyBagExceptionMother::random());
        $repository->add(DoggyBagExceptionMother::from(
            description: 'Specific description',
            criticalPointId: (string) \App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId::generate(),
            accountId: 'account-id',
            id: (string) \App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId::generate(),
        ));

        $filter = new DoggyBagExceptionFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new DoggyBagExceptionFilter(
            description: 'Specific description',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $doggyBagException = DoggyBagExceptionMother::registeredDoggyBagException();

        $repository->add($doggyBagException);

        $recoverDoggyBagException = $repository->find($doggyBagException->id());

        $this->assertNotNull($recoverDoggyBagException);

        $recoverDoggyBagException->remove();

        $repository->add($doggyBagException);

        $this->assertNull($repository->find($doggyBagException->id()));
    }

    abstract protected function createSUT(): DoggyBagExceptionRepository;
}
