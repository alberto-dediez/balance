<?php

namespace App\Tests\Unit\Security\Application\Account\Register;

use App\Security\Application\Account\Register\RegisterAccountRequest;
use App\Security\Application\Account\Register\RegisterAccountResponse;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountRegistered;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

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

    public function testIsHandle()
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterAccountRequest(
            name: 'Company ABCDE',
            active: true
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $authorizer = AccountAuthorizerMother::create();

        return new RegisterAccountService($repository, $authorizer);
    }
}
