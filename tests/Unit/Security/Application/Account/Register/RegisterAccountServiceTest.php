<?php

namespace App\Tests\Unit\Security\Application\Account\Register;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\Account\Account;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Security\Application\Account\Register\RegisterAccountResponse;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;

class RegisterAccountServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = new InMemoryAccountRepository();

        $user = UserMother::adminUser();

        $sut = $this->createSUT($repository);

        $request = new RegisterAccountRequest(
            name: 'Company ABC',
            active: true
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterAccountResponse);
        $this->assertNotEmpty((string) $result->accountId);
        $this->assertEquals(36, strlen((string) $result->accountId));

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertTrue($accountPersisted instanceof Account);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AccountRegistered);
        $this->assertEquals('Company ABC', $lastEvent->name());
        $this->assertTrue($accountPersisted->id()->equals($lastEvent->accountId()));
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $user = UserMother::ownerUser();

        $sut = $this->createSUT($repository);

        $request = new RegisterAccountRequest(
            name: 'Company ABC',
            active: true
        );

        $sut($request, $user);
    }

    private function createSUT($repository)
    {
        $authorizer = AccountAuthorizerMother::create();

        return new RegisterAccountService($repository, $authorizer);
    }
}