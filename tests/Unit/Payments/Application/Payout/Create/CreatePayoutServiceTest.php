<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Create;

use App\Payments\Application\Payout\Create\CreatePayoutRequest;
use App\Payments\Application\Payout\Create\CreatePayoutService;
use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payout\PayeeNotReadyException;
use App\Payments\Domain\Model\Payout\PayoutCreated;
use App\Payments\Infrastructure\Persistence\InMemory\Lock\InMemoryPayoutCreationLock;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;
use App\Payments\Infrastructure\Persistence\InMemory\Sequence\InMemoryPayoutNumberGenerator;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CreatePayoutServiceTest extends TestCase
{
    private InMemoryPayoutRepository $payouts;

    private InMemoryPayeeRepository $payees;

    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->payouts = PayoutRepositoryMother::inMemory();
        $this->payees = PayeeRepositoryMother::inMemory();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItCreatesAPayoutForAReadyPayee(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $response = $this->service()(
            new CreatePayoutRequest(
                payeeId: $payee->id(),
                baseAmount: 320.0,
                linesCount: 4,
            ),
            UserMother::adminUser(),
        );

        $stored = $this->payouts->find($response->payoutId);

        $this->assertNotEmpty((string) $response->payoutId);
        $this->assertNotEmpty($response->number);
        $this->assertNotNull($stored);
        $this->assertSame(320.0, $stored->baseAmount());
        $this->assertSame(4, $stored->linesCount());
        $this->assertSame($payee->id(), $stored->payeeId());

        $this->assertInstanceOf(PayoutCreated::class, $this->spySubscriber->lastEvent());
    }

    public function testItFailsWhenPayeeIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(
            new CreatePayoutRequest(
                payeeId: PayeeMother::ready()->id(),
                baseAmount: 100.0,
                linesCount: 1,
            ),
            UserMother::adminUser(),
        );
    }

    public function testItFailsWhenPayeeIsNotReady(): void
    {
        $payee = PayeeMother::incomplete();
        $this->payees->add($payee);

        $this->expectException(PayeeNotReadyException::class);

        $this->service()(
            new CreatePayoutRequest(
                payeeId: $payee->id(),
                baseAmount: 100.0,
                linesCount: 1,
            ),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotCreatePayouts(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new CreatePayoutRequest(
                payeeId: $payee->id(),
                baseAmount: 100.0,
                linesCount: 1,
            ),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): CreatePayoutService
    {
        return new CreatePayoutService(
            payoutRepository: $this->payouts,
            payeeRepository: $this->payees,
            numberGenerator: new InMemoryPayoutNumberGenerator($this->payouts),
            lock: new InMemoryPayoutCreationLock(),
            authorizer: new PayoutAuthorizer(),
        );
    }
}
