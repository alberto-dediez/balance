<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Send;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Application\Payout\Send\SendPayoutRequest;
use App\Payments\Application\Payout\Send\SendPayoutService;
use App\Payments\Domain\Model\Payout\PayeeNotReadyException;
use App\Payments\Domain\Model\Payout\PayoutFailed;
use App\Payments\Domain\Model\Payout\PayoutGateway;
use App\Payments\Domain\Model\Payout\PayoutGatewayException;
use App\Payments\Domain\Model\Payout\PayoutInstruction;
use App\Payments\Domain\Model\Payout\PayoutNotPendingException;
use App\Payments\Domain\Model\Payout\PayoutReference;
use App\Payments\Domain\Model\Payout\PayoutSent;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Payments\Infrastructure\Payout\Fake\FakePayoutGateway;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeMother;
use App\Tests\Unit\Payments\Domain\Model\Payee\PayeeRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SendPayoutServiceTest extends TestCase
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

    public function testItSendsADraftPayoutThroughTheGateway(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $payout = PayoutMother::draft($payee->id());
        $this->payouts->add($payout);

        $gateway = new FakePayoutGateway();

        $response = $this->service($gateway)(
            new SendPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );

        $stored = $this->payouts->find($payout->id());

        $this->assertSame(PayoutStatus::Sent, $response->status);
        $this->assertSame('fake', $stored?->gateway());
        $this->assertSame('fake_' . $payout->number(), $response->externalId);

        $this->assertInstanceOf(PayoutSent::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $gateway->sent());
    }

    public function testItFailsThePayoutWhenTheGatewayRejectsIt(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $payout = PayoutMother::draft($payee->id());
        $this->payouts->add($payout);

        $response = $this->service($this->failingGateway())(
            new SendPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );

        $stored = $this->payouts->find($payout->id());

        $this->assertSame(PayoutStatus::Failed, $response->status);
        $this->assertNotNull($stored);
        $this->assertSame(PayoutStatus::Failed, $stored->status());
        $this->assertSame('IBAN inválido', $stored->failureReason());

        $this->assertInstanceOf(PayoutFailed::class, $this->spySubscriber->lastEvent());
    }

    public function testItFailsWhenThePayeeIsNotReady(): void
    {
        $payee = PayeeMother::incomplete();
        $this->payees->add($payee);

        $payout = PayoutMother::draft($payee->id());
        $this->payouts->add($payout);

        $this->expectException(PayeeNotReadyException::class);

        $this->service(new FakePayoutGateway())(
            new SendPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testItCannotSendAPaidPayout(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $payout = PayoutMother::paid($payee->id());
        $this->payouts->add($payout);

        $this->expectException(PayoutNotPendingException::class);

        $this->service(new FakePayoutGateway())(
            new SendPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotSendPayouts(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $payout = PayoutMother::draft($payee->id());
        $this->payouts->add($payout);

        $this->expectException(UnauthorizedActionException::class);

        $this->service(new FakePayoutGateway())(
            new SendPayoutRequest($payout->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(PayoutGateway $gateway): SendPayoutService
    {
        return new SendPayoutService(
            payoutRepository: $this->payouts,
            payeeRepository: $this->payees,
            payoutGateway: $gateway,
            authorizer: new PayoutAuthorizer(),
        );
    }

    private function failingGateway(): PayoutGateway
    {
        return new class implements PayoutGateway {
            public function name(): string
            {
                return 'failing';
            }

            public function send(PayoutInstruction $instruction): PayoutReference
            {
                throw new PayoutGatewayException('IBAN inválido');
            }
        };
    }
}
