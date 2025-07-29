<?php

namespace App\Tests\Unit\Property\Domain\Model\Destination;

use App\Property\Domain\Model\Destination\DestinationFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Property\Domain\Model\Destination\DestinationRepository;

abstract class DestinationRepositoryTest extends KernelTestCase
{
    public function testAddDestinationToRepository()
    {   
        $repository = $this->createSUT();

        $destination = DestinationMother::random();

        $this->assertNull($repository->find($destination->id()));

        $repository->add($destination);

        $recoverDestination = $repository->find($destination->id());

        $this->assertNotNull($recoverDestination);
        $this->assertEquals($destination->id(), $recoverDestination->id());
    }

    public function testRemoveDestinationToRepository()
    {   
        $repository = $this->createSUT();

        $destination = DestinationMother::random();

        $repository->add($destination);

        $recoverDestination = $repository->find($destination->id());

        $this->assertNotNull($recoverDestination);
        $this->assertEquals($destination->id(), $recoverDestination->id());

        $repository->remove($destination);

        $this->assertNull($repository->find($destination->id()));
    }

    public function testFindByNameAndCountry()
    {
        $repository = $this->createSUT();

        $destination = DestinationMother::random();

        $this->assertNull($repository->findByNameAndCountry($destination->name(), $destination->countryId()));

        $repository->add($destination);

        $recoverDestination = $repository->findByNameAndCountry($destination->name(), $destination->countryId());

        $this->assertNotNull($recoverDestination);
        $this->assertEquals($destination->id(), $recoverDestination->id());
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::fromName('Spain'));

        $filter = new DestinationFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new DestinationFilter(
            name: 'Spain'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
    {
        $repository = $this->createSUT();

        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::fromName('Spain'));

        $filter = new DestinationFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new DestinationFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved()
    {
        $repository = $this->createSUT();

        $destination = DestinationMother::random();

        $repository->add($destination);

        $recoverDestination = $repository->find($destination->id());

        $this->assertNotNull($recoverDestination);

        $recoverDestination->remove();

        $repository->add($destination);

        $this->assertNull($repository->find($destination->id()));
    }

    abstract protected function createSUT(): DestinationRepository;
}
