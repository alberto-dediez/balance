<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payout\View;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Application\Payout\View\ViewPayoutRequest;
use App\Payments\Application\Payout\View\ViewPayoutService;
use App\Payments\Domain\Model\Payee\PayeeType;
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

class ViewPayoutServiceTest extends TestCase
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

    public function testItViewsAPayoutWithItsPayee(): void
    {
        $payee = PayeeMother::ready();
        $this->payees->add($payee);

        $payout = PayoutMother::draft($payee->id());
        $this->payouts->add($payout);

        $response = $this->service()(
            new ViewPayoutRequest($payout->id()),
            UserMother::adminUser(),
        );

        $this->assertSame($payout->id(), $response->payout->id());
        $this->assertNotNull($response->payee);
        $this->assertSame($payee->id(), $response->payee->id());
    }

    public function testItFailsWhenPayoutIsNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service()(
            new ViewPayoutRequest(PayoutMother::draft()->id()),
            UserMother::adminUser(),
        );
    }

    public function testAnOwnerCanViewOwnPayout(): void
    {
        $accountId = AccountId::generate();

        $ownPayee = PayeeMother::ready((string) $accountId, PayeeType::Account);
        $this->payees->add($ownPayee);

        $payout = PayoutMother::draft($ownPayee->id());
        $this->payouts->add($payout);

        $response = $this->service()(
            new ViewPayoutRequest($payout->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        );

        $this->assertSame($payout->id(), $response->payout->id());
    }

    public function testAnOwnerCannotViewAForeignPayout(): void
    {
        $accountId = AccountId::generate();

        $ownPayee = PayeeMother::ready((string) $accountId, PayeeType::Account);
        $this->payees->add($ownPayee);

        $foreignPayout = PayoutMother::random();
        $this->payouts->add($foreignPayout);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ViewPayoutRequest($foreignPayout->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        );
    }

    public function testANormalUserCannotViewPayouts(): void
    {
        $payout = PayoutMother::draft();
        $this->payouts->add($payout);

        $this->expectException(UnauthorizedActionException::class);

        $this->service()(
            new ViewPayoutRequest($payout->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function service(): ViewPayoutService
    {
        return new ViewPayoutService(
            repository: $this->payouts,
            payeeRepository: $this->payees,
            authorizer: new PayoutAuthorizer(),
        );
    }
}
