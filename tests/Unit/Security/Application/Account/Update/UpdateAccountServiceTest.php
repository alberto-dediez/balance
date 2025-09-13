<?php

namespace App\Tests\Unit\Security\Application\Account\Update;

use App\Security\Application\Account\Update\UpdateAccountRequest;
use App\Security\Application\Account\Update\UpdateAccountResponse;
use App\Security\Application\Account\Update\UpdateAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountNameChanged;
use App\Security\Domain\Model\Account\AccountStatusChanged;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

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

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'Company ABC',
            active: false
        );

        $result = $sut($request, $user);

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

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'Company ABC',
            active: true
        );

        $result = $sut($request, $user);

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

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'MyAccount',
            active: true
        );

        $result = $sut($request, $user);

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

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $user = UserMother::ownerUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'MyAccount',
            active: true
        );

        $sut($request, $user);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::fromName('MyAccount');

        $result = $sut->isHandled(new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'MyAccount',
            active: true
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $authorizer = AccountAuthorizerMother::create();

        return new UpdateAccountService($repository, $authorizer);
    }
}
