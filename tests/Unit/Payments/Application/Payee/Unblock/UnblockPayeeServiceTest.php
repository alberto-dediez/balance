<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payee\Unblock;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Application\Payee\Unblock\UnblockPayeeRequest;
use App\Payments\Application\Payee\Unblock\UnblockPayeeService;
use App\Payments\Domain\Model\Payee\PayeeStatus;
use App\Payments\Domain\Model\Payee\PayeeUnblocked;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UnblockPayeeServiceTest extends TestCase
{
    private InMemoryPayeeRepository $payees;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->payees = PayeeRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItUnblocksABlockedPayee(): void
    {
        $payee = PayeeMother::blocked();
        $this->payees->add($payee);

        $spy = $this->newSpy();

        $response = $this->service()(
            new UnblockPayeeRequest($payee->id()),
            UserMother::adminUser(),
        );

        $this->assertSame(PayeeStatus::Ready, $response->status);

        $persisted = $this->payees->find($payee->id());
        $this->assertNotNull($persisted);
        $this->assertFalse($persisted->isBlocked());

        $lastEvent = $spy->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(PayeeUnblocked::class, $lastEvent);
    }

    public function testItLeavesAnUnblockedPayeeUnchanged(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $spy = $this->newSpy();

        $response = $this->service()(
            new UnblockPayeeRequest($payee->id()),
            UserMother::adminUser(),
        );

        $this->assertSame(PayeeStatus::Ready, $response->status);
        $this->assertNull($spy->lastEvent());
    }

    public function testItRefusesWhenThePayeeIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(
            new UnblockPayeeRequest(PayeeMother::random()->id()),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotUnblockPayees(): void
    {
        $payee = PayeeMother::blocked();
        $this->payees->add($payee);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new UnblockPayeeRequest($payee->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): UnblockPayeeService
    {
        return new UnblockPayeeService($this->payees, new PayeeAuthorizer());
    }

    private function newSpy(): SpyDomainEventSubscriber
    {
        DomainEventPublisher::instance()->reset();

        return new SpyDomainEventSubscriber();
    }
}
