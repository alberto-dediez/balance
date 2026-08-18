<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\Remit;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Application\Payout\Remit\RemitPayoutsRequest;
use App\Payments\Application\Payout\Remit\RemitPayoutsService;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutMother;
use App\Tests\Unit\Payments\Domain\Model\Payout\PayoutRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RemitPayoutsServiceTest extends TestCase
{
    private InMemoryPayoutRepository $payouts;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->payouts = PayoutRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItMarksEveryDraftPayoutAsSent(): void
    {
        $payout = $this->draftPayout();
        $this->draftPayout();

        $response = $this->service()(
            new RemitPayoutsRequest(reference: 'remesa-20260814.xml'),
            UserMother::adminUser(),
        );

        $remitted = $this->payouts->find($payout->id());

        $this->assertSame(2, $response->remittedCount);
        $this->assertNotNull($remitted);
        $this->assertSame(PayoutStatus::Sent, $remitted->status());
        $this->assertSame('sepa', $remitted->gateway());
        $this->assertSame('remesa-20260814.xml', $remitted->externalId());
    }

    public function testItMarksOnlyTheRequestedPayouts(): void
    {
        $requested = $this->draftPayout();
        $untouched = $this->draftPayout();

        $response = $this->service()(
            new RemitPayoutsRequest(payoutIds: [$requested->id()]),
            UserMother::adminUser(),
        );

        $stored = $this->payouts->find($untouched->id());

        $this->assertSame(1, $response->remittedCount);
        $this->assertNotNull($stored);
        $this->assertSame(PayoutStatus::Draft, $stored->status());
    }

    public function testAnAlreadyRemittedPayoutIsNotRemittedAgain(): void
    {
        $this->draftPayout();

        $this->service()(new RemitPayoutsRequest(), UserMother::adminUser());

        $response = $this->service()(new RemitPayoutsRequest(), UserMother::adminUser());

        $this->assertSame(0, $response->remittedCount);
    }

    public function testAFailedPayoutCanBeRemittedAgain(): void
    {
        $payout = $this->draftPayout();
        $payout->fail('IBAN incorrecto');
        $this->payouts->add($payout);

        $response = $this->service()(
            new RemitPayoutsRequest(payoutIds: [$payout->id()]),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $response->remittedCount);
    }

    public function testAnOwnerCannotRemitPayouts(): void
    {
        $this->draftPayout();

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new RemitPayoutsRequest(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );
    }

    private function draftPayout(): Payout
    {
        $payout = PayoutMother::draft();

        $this->payouts->add($payout);

        return $payout;
    }

    private function service(): RemitPayoutsService
    {
        return new RemitPayoutsService($this->payouts, new PayoutAuthorizer());
    }
}
