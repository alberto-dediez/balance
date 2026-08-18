<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Cancel;

use App\Payments\Application\Payout\Cancel\CancelPayoutRequest;
use App\Payments\Application\Payout\Cancel\CancelPayoutService;
use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payout\PayoutCancelled;
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

class CancelPayoutServiceTest extends TestCase
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

    public function testItCancelsADraftPayout(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $response = $this->service()(
            new CancelPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );

        $stored = $this->payouts->find($payout->id());

        $this->assertSame(PayoutStatus::Cancelled, $response->status);
        $this->assertNotNull($stored);
        $this->assertSame(PayoutStatus::Cancelled, $stored->status());

        $this->assertInstanceOf(PayoutCancelled::class, $this->spySubscriber->lastEvent());
    }

    public function testItCancelsAFailedPayout(): void
    {
        $payout = PayoutMother::failed();
        $this->payouts->add($payout);

        $response = $this->service()(
            new CancelPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );

        $this->assertSame(PayoutStatus::Cancelled, $response->status);
    }

    public function testItCannotCancelAPaidPayout(): void
    {
        $payout = PayoutMother::paid();
        $this->payouts->add($payout);

        $this->expectException(PayoutNotPendingException::class);

        $this->service()(
            new CancelPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testItCannotCancelASentPayout(): void
    {
        $payout = PayoutMother::sent();
        $this->payouts->add($payout);

        $this->expectException(PayoutNotPendingException::class);

        $this->service()(
            new CancelPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotCancelPayouts(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new CancelPayoutRequest($payout->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): CancelPayoutService
    {
        return new CancelPayoutService($this->payouts, new PayoutAuthorizer());
    }
}
