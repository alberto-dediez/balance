<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Domain\Model\Property\PropertyScope;
use App\Property\Infrastructure\Persistence\Doctrine\Repository\DoctrinePropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePropertyRepositoryTest extends PropertyRepositoryTest
{
    public function testFindByFilterOrdersByNameByDefault(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(PropertyMother::from(PropertyId::generate(), 'Hotel Sierra', $accountId));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Apartamentos Mar', $accountId));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Sede Greenhost', $accountId));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Hotel Playa', $accountId));

        $result = $repository->findByFilter(new PropertyFilter(scope: PropertyScope::forAccount($accountId), limit: 0));

        $this->assertSame(
            ['Apartamentos Mar', 'Hotel Playa', 'Hotel Sierra', 'Sede Greenhost'],
            array_map(static fn(Property $property): string => $property->name(), $result),
        );
    }

    public function createSUT(): PropertyRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrinePropertyRepository($manager);
    }
}
