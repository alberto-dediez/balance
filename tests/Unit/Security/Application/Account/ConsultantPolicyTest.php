<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account;

use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountIsNotConsultantException;
use App\Security\Domain\Model\Account\ConsultantHasAssignedAccountsException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class ConsultantPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNullConsultantIsAlwaysAssignable(): void
    {
        $this->expectNotToPerformAssertions();

        $sut = new ConsultantPolicy(new InMemoryAccountRepository());

        $sut->denyUnlessAssignable(null);
    }

    public function testConsultantAccountIsAssignable(): void
    {
        $this->expectNotToPerformAssertions();

        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $repository->add($consultant);

        $sut = new ConsultantPolicy($repository);

        $sut->denyUnlessAssignable($consultant->id());
    }

    public function testAccountWithoutConsultantFlagIsNotAssignable(): void
    {
        $this->expectException(AccountIsNotConsultantException::class);

        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');
        $repository->add($account);

        $sut = new ConsultantPolicy($repository);

        $sut->denyUnlessAssignable($account->id());
    }

    public function testUnknownAccountIsNotAssignable(): void
    {
        $this->expectException(AccountIsNotConsultantException::class);

        $sut = new ConsultantPolicy(new InMemoryAccountRepository());

        $sut->denyUnlessAssignable(AccountId::generate());
    }

    public function testConsultantWithoutAssignedAccountsCanBeUnmarked(): void
    {
        $this->expectNotToPerformAssertions();

        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $repository->add($consultant);

        $sut = new ConsultantPolicy($repository);

        $sut->denyUnlessCanBeUnmarked($consultant);
    }

    public function testConsultantWithAssignedAccountsCannotBeUnmarked(): void
    {
        $this->expectException(ConsultantHasAssignedAccountsException::class);

        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $repository->add($consultant);
        $repository->add(AccountMother::fromNameWithConsultant('MyAccount', $consultant->id()));

        $sut = new ConsultantPolicy($repository);

        $sut->denyUnlessCanBeUnmarked($consultant);
    }
}
