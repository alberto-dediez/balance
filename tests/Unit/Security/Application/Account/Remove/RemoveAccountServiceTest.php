<?php

namespace App\Tests\Unit\Security\Application\Account\Remove;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Application\Account\Remove\RemoveAccountRequest;
use App\Security\Application\Account\Remove\RemoveAccountService;
use App\Security\Application\Account\Remove\RemoveAccountResponse;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;

class RemoveAccountServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = new InMemoryAccountRepository();

        $user = UserMother::adminUser();

        $sut = $this->createSUT($repository);

        $account = AccountMother::registeredAccount();

        $repository->add($account);

        $request = new RemoveAccountRequest(
            accountId: $account->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveAccountResponse);
        $this->assertTrue($account->id()->equals($result->accountId));
        
        $accountPersisted = $repository->find($result->accountId);

        $this->assertNull($accountPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AccountRemoved);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $user = UserMother::ownerUser();

        $sut = $this->createSUT($repository);

        $account = AccountMother::registeredAccount();

        $repository->add($account);

        $request = new RemoveAccountRequest(
            accountId: $account->id()
        );

        $sut($request, $user);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::fromName('MyAccount');

        $result = $sut->isHandled(new RemoveAccountRequest(
            accountId: $account->id()
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $authorizer = AccountAuthorizerMother::create();

        return new RemoveAccountService($repository, $authorizer);
    }
}