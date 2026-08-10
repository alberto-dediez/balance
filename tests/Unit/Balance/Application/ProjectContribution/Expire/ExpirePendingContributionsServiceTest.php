<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Expire;

use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpirePendingContributionsService;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ExpirePendingContributionsServiceTest extends TestCase
{
    private InMemoryProjectContributionRepository $contributions;

    protected function setUp(): void
    {
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
    }

    public function testSweepsEveryReservationPastItsWindow(): void
    {
        $first = ProjectContributionMother::overdue();
        $second = ProjectContributionMother::overdue();

        $this->contributions->add($first);
        $this->contributions->add($second);

        $response = $this->createSUT()(new ExpirePendingContributionsRequest(), UserMother::systemUser());

        $this->assertSame(2, $response->expired);
        $this->assertSame(0, $response->confirmed);
        $this->assertSame(ProjectContributionStatus::Expired, $first->status());
        $this->assertSame(ProjectContributionStatus::Expired, $second->status());
    }

    public function testLeavesReservationsWhoseWindowIsStillOpen(): void
    {
        $contribution = ProjectContributionMother::pending();
        $this->contributions->add($contribution);

        $response = $this->createSUT()(new ExpirePendingContributionsRequest(), UserMother::systemUser());

        $this->assertSame(0, $response->expired);
        $this->assertSame(ProjectContributionStatus::Pending, $contribution->status());
    }

    public function testConfirmsInsteadOfExpiringWhenTheGatewayAlreadyCharged(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::paid();
        $payments->add($payment);

        $paid = ProjectContributionMother::overdue(paymentId: $payment->id());
        $expired = ProjectContributionMother::overdue();

        $this->contributions->add($paid);
        $this->contributions->add($expired);

        $response = $this->createSUT($payments)(
            new ExpirePendingContributionsRequest(),
            UserMother::systemUser(),
        );

        $this->assertSame(1, $response->confirmed);
        $this->assertSame(1, $response->expired);
        $this->assertSame(ProjectContributionStatus::Paid, $paid->status());
        $this->assertSame(ProjectContributionStatus::Expired, $expired->status());
    }

    public function testAlreadyResolvedContributionsAreNotCounted(): void
    {
        $this->contributions->add(ProjectContributionMother::paid());
        $this->contributions->add(ProjectContributionMother::expired());

        $response = $this->createSUT()(new ExpirePendingContributionsRequest(), UserMother::systemUser());

        $this->assertSame(0, $response->expired);
        $this->assertSame(0, $response->confirmed);
    }

    public function testTheSweepDateDecidesWhatIsOverdue(): void
    {
        $contribution = ProjectContributionMother::pending(expiresAt: new \DateTimeImmutable('+2 hours'));
        $this->contributions->add($contribution);

        $response = $this->createSUT()(
            new ExpirePendingContributionsRequest(new \DateTimeImmutable('+3 hours')),
            UserMother::systemUser(),
        );

        $this->assertSame(1, $response->expired);
        $this->assertSame(ProjectContributionStatus::Expired, $contribution->status());
    }

    private function createSUT(?PaymentRepository $payments = null): ExpirePendingContributionsService
    {
        return new ExpirePendingContributionsService(
            $this->contributions,
            new ExpireProjectContributionService(
                $this->contributions,
                $payments ?? PaymentRepositoryMother::inMemory(),
            ),
        );
    }
}
