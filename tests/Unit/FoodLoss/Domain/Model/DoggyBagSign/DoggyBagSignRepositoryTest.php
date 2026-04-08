<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignFilter;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DoggyBagSignRepositoryTest extends KernelTestCase
{
    public function testAddDoggyBagSignToRepository(): void
    {
        $repository = $this->createSUT();

        $doggyBagSign = DoggyBagSignMother::registeredDoggyBagSign();

        $this->assertNull($repository->find($doggyBagSign->id()));

        $repository->add($doggyBagSign);

        $recovered = $repository->find($doggyBagSign->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($doggyBagSign->id(), $recovered->id());
    }

    public function testRemoveDoggyBagSignFromRepository(): void
    {
        $repository = $this->createSUT();

        $doggyBagSign = DoggyBagSignMother::registeredDoggyBagSign();

        $repository->add($doggyBagSign);

        $recovered = $repository->find($doggyBagSign->id());

        $this->assertNotNull($recovered);

        $repository->remove($doggyBagSign);

        $this->assertNull($repository->find($doggyBagSign->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(DoggyBagSignMother::random());
        $repository->add(DoggyBagSignMother::random());
        $repository->add(DoggyBagSignMother::random());
        $repository->add(DoggyBagSignMother::fromName('Cartel específico'));

        $filter = new DoggyBagSignFilter();
        $resultUnfiltered = $repository->findByFilter($filter);
        $this->assertCount(4, $resultUnfiltered);

        $filter = new DoggyBagSignFilter(name: 'Cartel específico');
        $result = $repository->findByFilter($filter);
        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(DoggyBagSignMother::random());
        $repository->add(DoggyBagSignMother::random());
        $repository->add(DoggyBagSignMother::random());
        $repository->add(DoggyBagSignMother::fromName('Cartel específico'));

        $filter = new DoggyBagSignFilter(limit: 0);
        $result = $repository->findByFilter($filter);
        $this->assertCount(4, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $doggyBagSign = DoggyBagSignMother::registeredDoggyBagSign();

        $repository->add($doggyBagSign);

        $recovered = $repository->find($doggyBagSign->id());

        $this->assertNotNull($recovered);

        $recovered->remove();

        $repository->add($recovered);

        $this->assertNull($repository->find($doggyBagSign->id()));
    }

    abstract protected function createSUT(): DoggyBagSignRepository;
}
