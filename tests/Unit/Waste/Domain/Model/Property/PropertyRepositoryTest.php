<?php

namespace App\Tests\Unit\Waste\Domain\Model\Property;

use App\Waste\Domain\Model\Property\PropertyFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Waste\Domain\Model\Property\PropertyRepository;

abstract class PropertyRepositoryTest extends KernelTestCase
{
    public function testAddPropertyToRepository()
    {   
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $this->assertNull($repository->find($property->id()));

        $repository->add($property);

        $recoverProperty = $repository->find($property->id());

        $this->assertNotNull($recoverProperty);
        $this->assertEquals($property->id(), $recoverProperty->id());
    }

    public function testRemovePropertyToRepository()
    {   
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $repository->add($property);

        $recoverProperty = $repository->find($property->id());

        $this->assertNotNull($recoverProperty);
        $this->assertEquals($property->id(), $recoverProperty->id());

        $repository->remove($property);

        $this->assertNull($repository->find($property->id()));
    }

    public function testFindByName()
    {
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $this->assertNull($repository->findByName($property->name()));

        $repository->add($property);

        $recoverProperty = $repository->findByName($property->name());

        $this->assertNotNull($recoverProperty);
        $this->assertEquals($property->id(), $recoverProperty->id());
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::fromName('Hotel'));

        $filter = new PropertyFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PropertyFilter(
            name: 'Hotel'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
    {
        $repository = $this->createSUT();

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::fromName('Hotel'));

        $filter = new PropertyFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PropertyFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved()
    {
        $repository = $this->createSUT();

        $property = PropertyMother::registeredProperty();

        $repository->add($property);

        $recoverProperty = $repository->find($property->id());

        $this->assertNotNull($recoverProperty);

        $recoverProperty->remove();

        $repository->add($property);

        $this->assertNull($repository->find($property->id()));
    }

    abstract protected function createSUT(): PropertyRepository;
}
