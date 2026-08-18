<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\List;

use App\Payments\Application\Payout\List\ListPayoutsRequest;
use App\Payments\Application\Payout\List\ListPayoutsService;
use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Domain\Model\Payout\PayoutStatus;
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
use PHPUnit\Framework\TestCase;

class ListPayoutsServiceTest extends TestCase
{
    private InMemoryPayoutRepository $payouts;

    private InMemoryPayeeRepository $payees;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->payouts = PayoutRepositoryMother::inMemory();
        $this->payees = PayeeRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testItListsAllPayoutsForAnAdmin(): void
    {
        $this->payouts->add(PayoutMother::random());
        $this->payouts->add(PayoutMother::random());

        $response = $this->service()(
            new ListPayoutsRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(2, $response->totalResults);
        $this->assertCount(2, $response->payouts);
    }

    public function testItFiltersByStatus(): void
    {
        $this->payouts->add(PayoutMother::draft());
        $this->payouts->add(PayoutMother::sent());
        $this->payouts->add(PayoutMother::paid());

        $response = $this->service()(
            new ListPayoutsRequest(status: PayoutStatus::Draft, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $response->totalResults);
        $this->assertCount(1, $response->payouts);
    }

    public function testAnOwnerOnlySeesPayoutsForOwnPayee(): void
    {
        $accountId = AccountId::generate();

        $ownPayee = PayeeMother::ready((string) $accountId, PayeeType::Account);
        $this->payees->add($ownPayee);

        $this->payouts->add(PayoutMother::draft($ownPayee->id()));
        $this->payouts->add(PayoutMother::random());

        $response = $this->service()(
            new ListPayoutsRequest(limit: 0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        );

        $this->assertSame(1, $response->totalResults);
        $this->assertCount(1, $response->payouts);
    }

    public function testAnOwnerWithoutPayeeSeesNothing(): void
    {
        $this->payouts->add(PayoutMother::random());

        $response = $this->service()(
            new ListPayoutsRequest(limit: 0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate()),
        );

        $this->assertSame(0, $response->totalResults);
        $this->assertCount(0, $response->payouts);
    }

    public function testANormalUserCannotListPayouts(): void
    {
        $this->payouts->add(PayoutMother::random());

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ListPayoutsRequest(limit: 0),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function service(): ListPayoutsService
    {
        return new ListPayoutsService(
            repository: $this->payouts,
            payeeRepository: $this->payees,
            authorizer: new PayoutAuthorizer(),
        );
    }
}
