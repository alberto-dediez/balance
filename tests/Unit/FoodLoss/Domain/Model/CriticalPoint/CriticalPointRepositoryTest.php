<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CriticalPointRepositoryTest extends KernelTestCase
{
    public function testAddCriticalPointToRepository(): void
    {
        $repository = $this->createSUT();

        $criticalPoint = CriticalPointMother::registeredCriticalPoint();

        $this->assertNull($repository->find($criticalPoint->id()));

        $repository->add($criticalPoint);

        $recoverCriticalPoint = $repository->find($criticalPoint->id());

        $this->assertNotNull($recoverCriticalPoint);
        $this->assertEquals($criticalPoint->id(), $recoverCriticalPoint->id());
    }

    public function testRemoveCriticalPointToRepository(): void
    {
        $repository = $this->createSUT();

        $criticalPoint = CriticalPointMother::registeredCriticalPoint();

        $repository->add($criticalPoint);

        $recoverCriticalPoint = $repository->find($criticalPoint->id());

        $this->assertNotNull($recoverCriticalPoint);
        $this->assertEquals($criticalPoint->id(), $recoverCriticalPoint->id());

        $repository->remove($criticalPoint);

        $this->assertNull($repository->find($criticalPoint->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $propertyId = PropertyId::generate();

        $repository->add(CriticalPointMother::random());
        $repository->add(CriticalPointMother::random());
        $repository->add(CriticalPointMother::random());
        $repository->add(CriticalPointMother::random());
        $repository->add(CriticalPointMother::random());
        $repository->add(CriticalPointMother::fromNameAndAccountAndProperty('Nombre específico', $accountId, $propertyId));

        $filter = new CriticalPointFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        // Filter by name
        $filter = new CriticalPointFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);

        // Filter by account
        $filter = new CriticalPointFilter(
            accountId: $accountId,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);

        // Filter by property
        $filter = new CriticalPointFilter(
            propertyId: $propertyId,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $criticalPoint = CriticalPointMother::registeredCriticalPoint();

        $repository->add($criticalPoint);

        $recoverCriticalPoint = $repository->find($criticalPoint->id());

        $this->assertNotNull($recoverCriticalPoint);

        $recoverCriticalPoint->remove();

        $repository->add($criticalPoint);

        $this->assertNull($repository->find($criticalPoint->id()));
    }

    abstract protected function createSUT(): CriticalPointRepository;
}
