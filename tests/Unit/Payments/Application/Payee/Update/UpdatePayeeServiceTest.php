<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payee\Update;

use App\Payments\Application\Payee\PayeeAuthorizer;
use App\Payments\Application\Payee\Update\UpdatePayeeRequest;
use App\Payments\Application\Payee\Update\UpdatePayeeService;
use App\Payments\Domain\Model\Payee\PayeeStatus;
use App\Payments\Domain\Model\Payee\PayeeUpdated;
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

class UpdatePayeeServiceTest extends TestCase
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

    public function testItUpdatesTheLegalNameAndBankData(): void
    {
        $payee = PayeeMother::incomplete();
        $this->payees->add($payee);

        $spy = $this->newSpy();

        $response = $this->service()(
            new UpdatePayeeRequest(
                payeeId: $payee->id(),
                legalName: 'Hotel Y SA',
                taxId: 'B87654321',
                street: 'Avenida Diagonal 99',
                postalCode: '08018',
                city: 'Barcelona',
                country: 'ES',
                iban: 'ES7621000418450200051332',
                bic: 'CAIXESBBXXX',
                holderName: 'Hotel Y SA',
                notes: 'Updated notes',
            ),
            UserMother::adminUser(),
        );

        $this->assertSame(PayeeStatus::Ready, $response->status);

        $persisted = $this->payees->find($payee->id());
        $this->assertNotNull($persisted);
        $this->assertSame('Hotel Y SA', $persisted->legalName());
        $this->assertSame('B87654321', $persisted->taxId());
        $this->assertSame('ES7621000418450200051332', $persisted->iban());
        $this->assertSame('Hotel Y SA', $persisted->holderName());
        $this->assertSame('Updated notes', $persisted->notes());

        $lastEvent = $spy->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(PayeeUpdated::class, $lastEvent);
    }

    public function testItKeepsABlockedPayeeBlockedAfterUpdate(): void
    {
        $payee = PayeeMother::blocked();
        $this->payees->add($payee);

        $response = $this->service()(
            new UpdatePayeeRequest(
                payeeId: $payee->id(),
                legalName: 'Hotel Y SA',
                taxId: 'B87654321',
                iban: 'ES7621000418450200051332',
                holderName: 'Hotel Y SA',
            ),
            UserMother::adminUser(),
        );

        $this->assertSame(PayeeStatus::Blocked, $response->status);

        $persisted = $this->payees->find($payee->id());
        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isBlocked());
    }

    public function testItRefusesWhenThePayeeIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(
            new UpdatePayeeRequest(
                payeeId: PayeeMother::random()->id(),
                legalName: 'Hotel Y SA',
            ),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotUpdatePayees(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new UpdatePayeeRequest(
                payeeId: $payee->id(),
                legalName: 'Hotel Y SA',
            ),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): UpdatePayeeService
    {
        return new UpdatePayeeService($this->payees, new PayeeAuthorizer());
    }

    private function newSpy(): SpyDomainEventSubscriber
    {
        DomainEventPublisher::instance()->reset();

        return new SpyDomainEventSubscriber();
    }
}
