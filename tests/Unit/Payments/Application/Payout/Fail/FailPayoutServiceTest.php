<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Fail;

use App\Payments\Application\Payout\Fail\FailPayoutRequest;
use App\Payments\Application\Payout\Fail\FailPayoutService;
use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payout\PayoutFailed;
use App\Payments\Domain\Model\Payout\PayoutNotPendingException;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class FailPayoutServiceTest extends TestCase
{
    private InMemoryPayoutRepository $payouts;

    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->payouts = PayoutRepositoryMother::inMemory();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItFailsADraftPayout(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $response = $this->service()(
            new FailPayoutRequest($payout->id(), reason: 'IBAN incorrecto'),
            UserMother::adminUser(),
        );

        $stored = $this->payouts->find($payout->id());

        $this->assertSame(PayoutStatus::Failed, $response->status);
        $this->assertNotNull($stored);
        $this->assertSame(PayoutStatus::Failed, $stored->status());
        $this->assertSame('IBAN incorrecto', $stored->failureReason());

        $this->assertInstanceOf(PayoutFailed::class, $this->spySubscriber->lastEvent());
    }

    public function testItCannotFailAPaidPayout(): void
    {
        $payout = PayoutMother::paid();
        $this->payouts->add($payout);

        $this->expectException(PayoutNotPendingException::class);

        $this->service()(
            new FailPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testItCannotFailACancelledPayout(): void
    {
        $payout = PayoutMother::cancelled();
        $this->payouts->add($payout);

        $this->expectException(PayoutNotPendingException::class);

        $this->service()(
            new FailPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotFailPayouts(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new FailPayoutRequest($payout->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): FailPayoutService
    {
        return new FailPayoutService($this->payouts, new PayoutAuthorizer());
    }
}
