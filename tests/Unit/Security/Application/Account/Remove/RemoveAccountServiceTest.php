<?php

namespace App\Tests\Unit\Security\Application\Account\Remove;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Security\Application\Account\Remove\RemoveAccountRequest;
use App\Security\Application\Account\Remove\RemoveAccountService;
use App\Security\Application\Account\Remove\RemoveAccountResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
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

        $sut = $this->createSUT($repository);

        $account = AccountMother::registeredAccount();

        $repository->add($account);

        $request = new RemoveAccountRequest(
            accountId: $account->id()
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveAccountResponse);
        $this->assertTrue($account->id()->equals($result->accountId));
        
        $accountPersisted = $repository->find($result->accountId);

        $this->assertNull($accountPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AccountRemoved);
    }

    private function createSUT($repository)
    {
        return new RemoveAccountService($repository);
    }
}