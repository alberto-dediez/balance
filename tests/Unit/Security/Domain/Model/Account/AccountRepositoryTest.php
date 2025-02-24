<?php

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\AccountRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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

    abstract protected function createSUT(): AccountRepository;
}
