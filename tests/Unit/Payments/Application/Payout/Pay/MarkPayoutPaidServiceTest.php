<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Pay;

use App\Payments\Application\Payout\Pay\MarkPayoutPaidRequest;
use App\Payments\Application\Payout\Pay\MarkPayoutPaidService;
use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payout\PayoutNotPendingException;
use App\Payments\Domain\Model\Payout\PayoutPaid;
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

class MarkPayoutPaidServiceTest extends TestCase
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

    public function testItMarksADraftPayoutAsPaid(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $response = $this->service()(
            new MarkPayoutPaidRequest($payout->id(), reference: 'TRF-100'),
            UserMother::adminUser(),
        );

        $stored = $this->payouts->find($payout->id());

        $this->assertSame(PayoutStatus::Paid, $response->status);
        $this->assertNotNull($stored);
        $this->assertSame(PayoutStatus::Paid, $stored->status());
        $this->assertSame('TRF-100', $stored->reference());

        $this->assertInstanceOf(PayoutPaid::class, $this->spySubscriber->lastEvent());
    }

    public function testItMarksASentPayoutAsPaid(): void
    {
        $payout = PayoutMother::sent();
        $this->payouts->add($payout);

        $response = $this->service()(
            new MarkPayoutPaidRequest($payout->id(), reference: 'TRF-101'),
            UserMother::adminUser(),
        );

        $this->assertSame(PayoutStatus::Paid, $response->status);
    }

    public function testItCannotMarkAPaidPayoutAsPaid(): void
    {
        $payout = PayoutMother::paid();
        $this->payouts->add($payout);

        $this->expectException(PayoutNotPendingException::class);

        $this->service()(
            new MarkPayoutPaidRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testItCannotMarkACancelledPayoutAsPaid(): void
    {
        $payout = PayoutMother::cancelled();
        $this->payouts->add($payout);

        $this->expectException(PayoutNotPendingException::class);

        $this->service()(
            new MarkPayoutPaidRequest($payout->id()),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCannotMarkPayoutsAsPaid(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new MarkPayoutPaidRequest($payout->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function service(): MarkPayoutPaidService
    {
        return new MarkPayoutPaidService($this->payouts, new PayoutAuthorizer());
    }
}
