<?php

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\AccountFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Security\Domain\Model\Account\AccountRepository;

abstract class AccountRepositoryTest extends KernelTestCase
{
    public function testAddAccountToRepository()
    {   
        $repository = $this->createSUT();

        $account = AccountMother::registeredAccount();

        $this->assertNull($repository->find($account->id()));

        $repository->add($account);

        $recoverAccount = $repository->find($account->id());

        $this->assertNotNull($recoverAccount);
        $this->assertEquals($account->id(), $recoverAccount->id());
    }

    public function testRemoveAccountToRepository()
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

    public function testFindByName()
    {
        $repository = $this->createSUT();

        $account = AccountMother::registeredAccount();

        $this->assertNull($repository->findByName($account->name()));

        $repository->add($account);

        $recoverAccount = $repository->findByName($account->name());

        $this->assertNotNull($recoverAccount);
        $this->assertEquals($account->id(), $recoverAccount->id());
    }

    public function testFindByFilter()
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
            name: 'Minotour'
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    abstract protected function createSUT(): AccountRepository;
}
