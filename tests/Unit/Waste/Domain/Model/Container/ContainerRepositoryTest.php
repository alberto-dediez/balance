<?php

namespace App\Tests\Unit\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Container\ContainerFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;

abstract class ContainerRepositoryTest extends KernelTestCase
{
    public function testAddContainerToRepository()
    {   
        $repository = $this->createSUT();

        $container = ContainerMother::random();

        $this->assertNull($repository->find($container->id()));

        $repository->add($container);

        $recoverContainer = $repository->find($container->id());

        $this->assertNotNull($recoverContainer);
        $this->assertEquals($container->id(), $recoverContainer->id());
    }

    public function testRemoveContainerToRepository()
    {   
        $repository = $this->createSUT();

        $container = ContainerMother::random();

        $repository->add($container);

        $recoverContainer = $repository->find($container->id());

        $this->assertNotNull($recoverContainer);
        $this->assertEquals($container->id(), $recoverContainer->id());

        $repository->remove($container);

        $this->assertNull($repository->find($container->id()));
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $property = PropertyMother::fromName('Hotel');
        $waste = WasteMother::fromName('Plastico');

        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::fromPropertyAndWaste(
                (string) $property->id(), (string) $waste->id()
            )
        );

        $filter = new ContainerFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ContainerFilter(
            wasteId: $waste->id(),
            propertyId: $property->id()
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements()
    {
        $repository = $this->createSUT();

        $property = PropertyMother::fromName('Hotel');
        $waste = WasteMother::fromName('Plastico');

        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::random()
        );
        $repository->add(
            ContainerMother::fromPropertyAndWaste(
                (string) $property->id(), (string) $waste->id()
            )
        );

        $filter = new ContainerFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ContainerFilter(
            limit: 0
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved()
    {
        $repository = $this->createSUT();

        $container = ContainerMother::random();

        $repository->add($container);

        $recoverContainer = $repository->find($container->id());

        $this->assertNotNull($recoverContainer);

        $recoverContainer->remove();

        $repository->add($container);

        $this->assertNull($repository->find($container->id()));
    }

    abstract protected function createSUT(): ContainerRepository;
}
