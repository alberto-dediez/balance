<?php

namespace App\Tests\Unit\Security\Application\UpdateAccount;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\Account\Account;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Security\Application\UpdateAccount\UpdateAccountRequest;
use App\Security\Application\UpdateAccount\UpdateAccountService;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Application\UpdateAccount\UpdateAccountResponse;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Security\Application\UpdateAccount\DuplicateAccountnameException;
use App\Security\Domain\Model\Account\AccountNameChanged;
use App\Security\Domain\Model\Account\AccountStatusChanged;
use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;

class UpdateAccountServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCaseChangeNameAndState()
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'Company ABC',
            active: false
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateAccountResponse);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertTrue($accountPersisted instanceof Account);

        $events = $this->spySubscriber->events();

        $this->assertGreaterThanOrEqual(2, count($events));

        $eventFirst = $events[count($events)-2];
        $eventLast = $events[count($events)-1];

        $this->assertTrue($eventFirst instanceof AccountNameChanged);
        $this->assertTrue($eventLast instanceof AccountStatusChanged);
    }

    public function testUseWhenOnlyChangeName()
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'Company ABC',
            active: true
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateAccountResponse);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertTrue($accountPersisted instanceof Account);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AccountNameChanged);
        $this->assertEquals('Company ABC', $lastEvent->name());
        $this->assertTrue($accountPersisted->id()->equals($lastEvent->accountId()));
    }

    public function testUseWhenNothingChange()
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'MyAccount',
            active: true
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateAccountResponse);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertTrue($accountPersisted instanceof Account);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertFalse($lastEvent instanceof AccountNameChanged);
        $this->assertFalse($lastEvent instanceof AccountStatusChanged);
    }

    private function createSUT($repository)
    {
        return new UpdateAccountService($repository);
    }
}