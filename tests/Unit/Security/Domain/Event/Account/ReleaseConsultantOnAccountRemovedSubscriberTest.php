<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Event\Account;

use App\Security\Domain\Event\Account\ReleaseConsultantOnAccountRemovedSubscriber;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class ReleaseConsultantOnAccountRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToAccountRemoved(): void
    {
        $sut = $this->createSUT(new InMemoryAccountRepository());

        $this->assertTrue($sut->isSubscribedTo(new AccountRemoved(AccountId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(new InMemoryAccountRepository());

        $this->assertFalse($sut->isSubscribedTo(new AccountRegistered(
            accountId: AccountId::generate(),
            name: 'account-name',
            active: true,
        )));
    }

    public function testHandleReleasesAccountsAssignedToTheRemovedConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $otherConsultant = AccountMother::consultantAccount();

        $account = AccountMother::fromNameWithConsultant('MyAccount', $consultant->id());
        $other = AccountMother::fromNameWithConsultant('OtherAccount', $otherConsultant->id());

        $repository->add($consultant);
        $repository->add($otherConsultant);
        $repository->add($account);
        $repository->add($other);

        $sut = $this->createSUT($repository);
        $sut->handle(new AccountRemoved($consultant->id()));

        $this->assertCount(0, $repository->findByFilter(new AccountFilter(consultantAccountId: $consultant->id(), limit: 0)));

        $released = $repository->find($account->id());
        $this->assertNotNull($released);
        $this->assertNull($released->consultantAccountId());

        $untouched = $repository->find($other->id());
        $this->assertNotNull($untouched);
        $this->assertNotNull($untouched->consultantAccountId());
        $this->assertTrue($otherConsultant->id()->equals($untouched->consultantAccountId()));
    }

    private function createSUT(AccountRepository $accountRepository): ReleaseConsultantOnAccountRemovedSubscriber
    {
        return new ReleaseConsultantOnAccountRemovedSubscriber($accountRepository);
    }
}
