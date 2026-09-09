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

    public function testTotalResultsByFilterIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());

        $filter = new AccountFilter(
            limit: 2,
        );

        $this->assertCount(2, $repository->findByFilter($filter));

        $this->assertEquals(6, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterByConsultantFlag(): void
    {
        $repository = $this->createSUT();

        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::consultantAccount());

        $this->assertCount(1, $repository->findByFilter(new AccountFilter(consultant: true, limit: 0)));
        $this->assertCount(2, $repository->findByFilter(new AccountFilter(consultant: false, limit: 0)));
    }

    public function testFindByFilterByConsultantAccountId(): void
    {
        $repository = $this->createSUT();

        $consultant = AccountMother::consultantAccount();
        $otherConsultant = AccountMother::consultantAccount();

        $repository->add($consultant);
        $repository->add($otherConsultant);
        $repository->add(AccountMother::fromNameWithConsultant('Minotour', $consultant->id()));
        $repository->add(AccountMother::fromNameWithConsultant('Bioscore', $consultant->id()));
        $repository->add(AccountMother::fromNameWithConsultant('Dediez', $otherConsultant->id()));

        $result = $repository->findByFilter(new AccountFilter(consultantAccountId: $consultant->id(), limit: 0));

        $this->assertCount(2, $result);
    }

    abstract protected function createSUT(): AccountRepository;
}
