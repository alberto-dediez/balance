<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Destination;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Domain\Model\Destination\DestinationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DestinationRepositoryTest extends KernelTestCase
{
    public function testAddDestinationToRepository(): void
    {
        $repository = $this->createSUT();

        $destination = DestinationMother::random();

        $this->assertNull($repository->find($destination->id()));

        $repository->add($destination);

        $recoverDestination = $repository->find($destination->id());

        $this->assertNotNull($recoverDestination);
        $this->assertEquals($destination->id(), $recoverDestination->id());
    }

    public function testRemoveDestinationToRepository(): void
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

    public function testFindByNameAndCountry(): void
    {
        $repository = $this->createSUT();

        $destination = DestinationMother::random();

        $countryId = $destination->countryId();
        assert($countryId instanceof CountryId);

        $this->assertNull($repository->findByNameAndCountry($destination->name(), $countryId));

        $repository->add($destination);

        $recoverDestination = $repository->findByNameAndCountry($destination->name(), $countryId);

        $this->assertNotNull($recoverDestination);
        $this->assertEquals($destination->id(), $recoverDestination->id());
    }

    public function testFindByFilter(): void
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
            name: 'Spain',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
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
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
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

    public function testTotalResultsByFilterIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());
        $repository->add(DestinationMother::random());

        $filter = new DestinationFilter(
            limit: 2,
        );

        $this->assertCount(2, $repository->findByFilter($filter));

        $this->assertEquals(6, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): DestinationRepository;
}
