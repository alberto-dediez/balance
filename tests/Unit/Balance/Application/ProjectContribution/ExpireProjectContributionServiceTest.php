<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution;

use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionService;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPaymentRepository;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ExpireProjectContributionServiceTest extends TestCase
{
    public function testExpiresAContributionPastItsWindow(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::overdue();
        $contributions->add($contribution);

        $response = (new ExpireProjectContributionService($contributions, PaymentRepositoryMother::inMemory()))(
            new ExpireProjectContributionRequest($contribution->id()),
            UserMother::systemUser(),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(ProjectContributionStatus::Expired, $contribution->status());
    }

    public function testKeepsAContributionWhoseWindowIsStillOpen(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending();
        $contributions->add($contribution);

        $response = (new ExpireProjectContributionService($contributions, PaymentRepositoryMother::inMemory()))(
            new ExpireProjectContributionRequest($contribution->id()),
            UserMother::systemUser(),
        );

        $this->assertFalse($response->changed);
        $this->assertSame(ProjectContributionStatus::Pending, $contribution->status());
    }

    public function testConfirmsInsteadOfExpiringWhenTheGatewayAlreadyCharged(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::paid();
        $payments->add($payment);

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::overdue(paymentId: $payment->id());
        $contributions->add($contribution);

        $response = (new ExpireProjectContributionService($contributions, $payments))(
            new ExpireProjectContributionRequest($contribution->id()),
            UserMother::systemUser(),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(ProjectContributionStatus::Paid, $contribution->status());
    }

    public function testAnExpiredCheckoutSessionReleasesTheCreditBeforeTheWindowIsOver(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::expired();
        $payments->add($payment);

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending(paymentId: $payment->id());
        $contributions->add($contribution);

        $response = (new ExpireProjectContributionService($contributions, $payments))(
            new ExpireProjectContributionRequest($contribution->id()),
            UserMother::systemUser(),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(ProjectContributionStatus::Expired, $contribution->status());
    }

    public function testAPendingPaymentKeepsTheReservationUntilItsWindowIsOver(): void
    {
        $payments = PaymentRepositoryMother::inMemory();
        $payment = PaymentMother::pending();
        $payments->add($payment);

        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::pending(paymentId: $payment->id());
        $contributions->add($contribution);

        $response = (new ExpireProjectContributionService($contributions, $payments))(
            new ExpireProjectContributionRequest($contribution->id()),
            UserMother::systemUser(),
        );

        $this->assertFalse($response->changed);
        $this->assertSame(ProjectContributionStatus::Pending, $contribution->status());
    }

    public function testAnAlreadyResolvedContributionIsLeftAlone(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::paid();
        $contributions->add($contribution);

        $response = (new ExpireProjectContributionService($contributions, PaymentRepositoryMother::inMemory()))(
            new ExpireProjectContributionRequest($contribution->id()),
            UserMother::systemUser(),
        );

        $this->assertFalse($response->changed);
        $this->assertSame(ProjectContributionStatus::Paid, $contribution->status());
    }

    public function testThrowsWhenTheContributionDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ExpireProjectContributionService(
            ProjectContributionRepositoryMother::inMemory(),
            new InMemoryPaymentRepository(),
        ))(new ExpireProjectContributionRequest(ProjectContributionMother::pending()->id()), UserMother::systemUser());
    }

    public function testAMissingPaymentDoesNotBlockTheExpiration(): void
    {
        $contributions = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::overdue(paymentId: PaymentId::generate());
        $contributions->add($contribution);

        $response = (new ExpireProjectContributionService($contributions, PaymentRepositoryMother::inMemory()))(
            new ExpireProjectContributionRequest($contribution->id()),
            UserMother::systemUser(),
        );

        $this->assertTrue($response->changed);
        $this->assertSame(ProjectContributionStatus::Expired, $contribution->status());
    }
}
