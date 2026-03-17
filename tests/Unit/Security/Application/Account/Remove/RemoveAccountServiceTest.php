<?php

namespace App\Tests\Unit\Security\Application\Account\Remove;

use App\Security\Application\Account\Remove\RemoveAccountRequest;
use App\Security\Application\Account\Remove\RemoveAccountResponse;
use App\Security\Application\Account\Remove\RemoveAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveAccountServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
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

        $this->assertTrue($account->id()->equals($result->accountId));

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNull($accountPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AccountRemoved);
    }

    public function testUnauthorizedAccess(): void
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

    public function testIsHandle(): void
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::fromName('MyAccount');

        $result = $sut->isHandled(new RemoveAccountRequest(
            accountId: $account->id()
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryAccountRepository $repository): RemoveAccountService
    {
        $authorizer = AccountAuthorizerMother::create();

        return new RemoveAccountService($repository, $authorizer);
    }
}
