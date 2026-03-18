<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AccountRepositoryTest extends KernelTestCase
{
    public function testAddAccountToRepository(): void
    {
        $repository = $this->createSUT();

        $account = AccountMother::registeredAccount();

        $this->assertNull($repository->find($account->id()));

        $repository->add($account);

        $recoverAccount = $repository->find($account->id());

        $this->assertNotNull($recoverAccount);
        $this->assertEquals($account->id(), $recoverAccount->id());
    }

    public function testRemoveAccountToRepository(): void
    {
        $repository = $this->createSUT();

        $account = AccountMother::registeredAccount();

        $repository->add($account);

        $recoverAccount = $repository->find($account->id());

        $this->assertNotNull($recoverAccount);
        $this->assertEquals($account->id(), $recoverAccount->id());

        $repository->remove($account);

        $this->assertNull($repository->find($account->id()));
    }

    public function testFindByName(): void
    {
        $repository = $this->createSUT();

        $account = AccountMother::registeredAccount();

        $this->assertNull($repository->findByName($account->name()));

        $repository->add($account);

        $recoverAccount = $repository->findByName($account->name());

        $this->assertNotNull($recoverAccount);
        $this->assertEquals($account->id(), $recoverAccount->id());
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::fromName('Minotour'));

        $filter = new AccountFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new AccountFilter(
            name: 'Minotour',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::fromName('Minotour'));

        $filter = new AccountFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new AccountFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverDeletedUser(): void
    {
        $repository = $this->createSUT();

        $account = AccountMother::registeredAccount();

        $repository->add($account);

        $recoverAccount = $repository->find($account->id());

        $this->assertNotNull($recoverAccount);

        $recoverAccount->remove();

        $repository->add($account);

        $this->assertNull($repository->find($account->id()));
    }

    abstract protected function createSUT(): AccountRepository;
}
