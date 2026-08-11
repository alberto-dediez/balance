<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Event\ProjectContribution;

use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Application\ProjectContribution\Confirm\ConfirmProjectContributionService;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionService;
use App\Balance\Application\ProjectContribution\Fail\FailProjectContributionService;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionService;
use App\Balance\Domain\Event\ProjectContribution\ExpireContributionOnPaymentExpiredSubscriber;
use App\Balance\Domain\Event\ProjectContribution\FailContributionOnPaymentFailedSubscriber;
use App\Balance\Domain\Event\ProjectContribution\PayContributionOnPaymentSucceededSubscriber;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Payments\Domain\Model\Payment\PaymentExpired;
use App\Payments\Domain\Model\Payment\PaymentFailed;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentSucceeded;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ProjectContribution\FakeCreditReservationLock;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentMother;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use App\Tests\Unit\Security\Application\User\SystemActor\SystemActorResolverMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ContributionPaymentSubscribersTest extends TestCase
{
    private InMemoryProjectContributionRepository $contributions;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->contributions = ProjectContributionRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testASucceededPaymentPaysItsContribution(): void
    {
        $contribution = ProjectContributionMother::pending();
        $this->contributions->add($contribution);

        new PayContributionOnPaymentSucceededSubscriber(
            $this->confirmService(),
        );

        $spy = new SpyDomainEventSubscriber();

        PaymentMother::pending(subjectId: (string) $contribution->id())->succeed('pi_1');

        $this->assertSame(ProjectContributionStatus::Paid, $contribution->status());
        $this->assertNotEmpty($spy->events());
    }

    public function testAFailedPaymentFailsItsContribution(): void
    {
        $contribution = ProjectContributionMother::pending();
        $this->contributions->add($contribution);

        new FailContributionOnPaymentFailedSubscriber(
            new FailProjectContributionService($this->contributions),
        );

        PaymentMother::pending(subjectId: (string) $contribution->id())->fail('card_declined');

        $this->assertSame(ProjectContributionStatus::Failed, $contribution->status());
        $this->assertSame('card_declined', $contribution->failureReason());
    }

    public function testAnExpiredPaymentExpiresItsContribution(): void
    {
        $contribution = ProjectContributionMother::overdue();
        $this->contributions->add($contribution);

        new ExpireContributionOnPaymentExpiredSubscriber(
            new ExpireProjectContributionService($this->contributions, PaymentRepositoryMother::inMemory()),
            SystemActorResolverMother::withPaymentUser(),
        );

        PaymentMother::pending(subjectId: (string) $contribution->id())->expire();

        $this->assertSame(ProjectContributionStatus::Expired, $contribution->status());
    }

    public function testPaymentsOfAnotherSubjectAreIgnored(): void
    {
        $subscriber = new PayContributionOnPaymentSucceededSubscriber(
            $this->confirmService(),
        );

        $this->assertFalse($subscriber->isSubscribedTo(new PaymentSucceeded(
            paymentId: PaymentId::generate(),
            subjectType: 'other.subject',
            subjectId: 'whatever',
        )));

        $this->assertTrue($subscriber->isSubscribedTo(new PaymentSucceeded(
            paymentId: PaymentId::generate(),
            subjectType: RegisterProjectContributionService::SUBJECT_TYPE,
            subjectId: 'whatever',
        )));
    }

    public function testTheFailAndExpireSubscribersAlsoFilterBySubjectType(): void
    {
        $failSubscriber = new FailContributionOnPaymentFailedSubscriber(
            new FailProjectContributionService($this->contributions),
        );
        $expireSubscriber = new ExpireContributionOnPaymentExpiredSubscriber(
            new ExpireProjectContributionService($this->contributions, PaymentRepositoryMother::inMemory()),
            SystemActorResolverMother::withPaymentUser(),
        );

        $this->assertFalse($failSubscriber->isSubscribedTo(new PaymentFailed(
            paymentId: PaymentId::generate(),
            subjectType: 'other.subject',
            subjectId: 'whatever',
            reason: 'nope',
        )));

        $this->assertFalse($expireSubscriber->isSubscribedTo(new PaymentExpired(
            paymentId: PaymentId::generate(),
            subjectType: 'other.subject',
            subjectId: 'whatever',
        )));
    }

    /** The project catalogue is empty here, so credit allocation stays out of the way of these cases. */
    private function confirmService(): ConfirmProjectContributionService
    {
        return new ConfirmProjectContributionService(
            $this->contributions,
            new AllocateContributionCreditsService(
                $this->contributions,
                CreditPurchaseRepositoryMother::inMemory(),
                ProjectRepositoryMother::inMemory(),
                new FakeCreditReservationLock(),
            ),
        );
    }
}
