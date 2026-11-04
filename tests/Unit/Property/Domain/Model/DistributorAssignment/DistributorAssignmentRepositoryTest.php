<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentFilter;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DistributorAssignmentRepositoryTest extends KernelTestCase
{
    public function testFind(): void
    {
        $repository = $this->createSUT();

        $assignment = DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate());

        $this->assertNull($repository->find($assignment->id()));

        $repository->add($assignment);

        $recovered = $repository->find($assignment->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($assignment->id()->equals($recovered->id()));
    }

    public function testFindForAccount(): void
    {
        $repository = $this->createSUT();

        $distributorAccountId = AccountId::generate();
        $accountId = AccountId::generate();

        $assignment = DistributorAssignmentMother::forAccount($distributorAccountId, $accountId);

        $repository->add($assignment);
        $repository->add(DistributorAssignmentMother::forAccount(AccountId::generate(), $accountId));

        $found = $repository->findForAccount($distributorAccountId, $accountId);

        $this->assertNotNull($found);
        $this->assertTrue($assignment->id()->equals($found->id()));
        $this->assertNull($repository->findForAccount($distributorAccountId, AccountId::generate()));
    }

    public function testFindForProperty(): void
    {
        $repository = $this->createSUT();

        $distributorAccountId = AccountId::generate();
        $propertyId = PropertyId::generate();

        $assignment = DistributorAssignmentMother::forProperty($distributorAccountId, $propertyId);

        $repository->add($assignment);

        $found = $repository->findForProperty($distributorAccountId, $propertyId);

        $this->assertNotNull($found);
        $this->assertTrue($assignment->id()->equals($found->id()));
        $this->assertNull($repository->findForProperty(AccountId::generate(), $propertyId));
    }

    public function testFindByFilterByDistributor(): void
    {
        $repository = $this->createSUT();

        $distributorAccountId = AccountId::generate();

        $repository->add(DistributorAssignmentMother::forAccount($distributorAccountId, AccountId::generate()));
        $repository->add(DistributorAssignmentMother::forProperty($distributorAccountId, PropertyId::generate()));
        $repository->add(DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate()));

        $filter = new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, limit: 0);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterAccountOrProperties(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();
        $ownPropertyId = PropertyId::generate();

        $byAccount = DistributorAssignmentMother::forAccount(AccountId::generate(), $accountId);
        $byProperty = DistributorAssignmentMother::forProperty(AccountId::generate(), $ownPropertyId);

        $repository->add($byAccount);
        $repository->add($byProperty);
        $repository->add(DistributorAssignmentMother::forAccount(AccountId::generate(), AccountId::generate()));
        $repository->add(DistributorAssignmentMother::forProperty(AccountId::generate(), PropertyId::generate()));

        $result = $repository->findByFilter(new DistributorAssignmentFilter(accountId: $accountId, propertyIds: [$ownPropertyId], limit: 0));

        $ids = array_map(static fn(DistributorAssignment $assignment): string => (string) $assignment->id(), $result);
        sort($ids);

        $expected = [(string) $byAccount->id(), (string) $byProperty->id()];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function testFindByFilterWithEmptyPropertyIdsReturnsNothing(): void
    {
        $repository = $this->createSUT();

        $repository->add(DistributorAssignmentMother::forProperty(AccountId::generate(), PropertyId::generate()));

        $filter = new DistributorAssignmentFilter(propertyIds: [], limit: 0);

        $this->assertSame([], $repository->findByFilter($filter));
        $this->assertSame(0, $repository->totalResultsByFilter($filter));
    }

    public function testRemovedAreExcluded(): void
    {
        $repository = $this->createSUT();

        $distributorAccountId = AccountId::generate();
        $accountId = AccountId::generate();

        $assignment = DistributorAssignmentMother::forAccount($distributorAccountId, $accountId);
        $repository->add($assignment);

        $assignment->remove(UserId::generate(), false);
        $repository->add($assignment);

        $this->assertNull($repository->find($assignment->id()));
        $this->assertNull($repository->findForAccount($distributorAccountId, $accountId));
        $this->assertSame([], $repository->findByFilter(new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, limit: 0)));
    }

    public function testPagination(): void
    {
        $repository = $this->createSUT();

        $distributorAccountId = AccountId::generate();

        for ($i = 0; $i < 5; ++$i) {
            $repository->add(DistributorAssignmentMother::forAccount($distributorAccountId, AccountId::generate()));
        }

        $filter = new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(5, $repository->totalResultsByFilter($filter));

        $filter->page = 2;

        $this->assertCount(1, $repository->findByFilter($filter));
    }

    abstract protected function createSUT(): DistributorAssignmentRepository;
}
