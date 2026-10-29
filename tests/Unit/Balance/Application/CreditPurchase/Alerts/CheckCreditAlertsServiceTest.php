<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Alerts;

use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsRequest;
use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsResponse;
use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsService;
use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Domain\Model\CreditPurchase\CreditAlertThreshold;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditThresholdReached;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Lock\InMemoryCreditReservationLock;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CheckCreditAlertsServiceTest extends TestCase
{
    private CreditPurchaseRepository $creditPurchaseRepository;

    private ProjectContributionRepository $projectContributionRepository;

    private CheckCreditAlertsService $service;

    private SpyDomainEventSubscriber $spySubscriber;

    private AccountId $accountId;

    private ProjectId $projectId;

    private User $user;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $this->projectContributionRepository = ProjectContributionRepositoryMother::inMemory();

        $this->service = new CheckCreditAlertsService(
            $this->creditPurchaseRepository,
            $this->projectContributionRepository,
            new CreditAvailability($this->creditPurchaseRepository, $this->projectContributionRepository),
            new InMemoryCreditReservationLock(),
        );

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->user = UserMother::adminUser();
    }

    public function testWithoutValidatedPurchasesNothingIsEvaluated(): void
    {
        $response = $this->check();

        $this->assertSame(0, $response->evaluated);
        $this->assertSame(0, $response->alerted);
    }

    public function testBelowTheFirstThresholdNoAlertIsPublished(): void
    {
        $creditPurchase = $this->validatedPurchase(10.0);
        $this->consume($creditPurchase, 7.0);

        $response = $this->check();

        $this->assertSame(1, $response->evaluated);
        $this->assertSame(0, $response->alerted);
        $this->assertSame([], $this->thresholdEvents());
    }

    public function testCrossingTheFirstThresholdPublishesTheWarning(): void
    {
        $creditPurchase = $this->validatedPurchase(10.0);
        $this->consume($creditPurchase, 8.5);

        $response = $this->check();

        $this->assertSame(1, $response->alerted);
        $this->assertSame([CreditAlertThreshold::Warning], $this->thresholdEvents());
    }

    public function testJumpingStraightToDepletionPublishesOnlyTheHighestThreshold(): void
    {
        $creditPurchase = $this->validatedPurchase(10.0);
        $this->consume($creditPurchase, 10.0);

        $this->check();

        $this->assertSame([CreditAlertThreshold::Depleted], $this->thresholdEvents());

        $reloaded = $this->creditPurchaseRepository->find($creditPurchase->id());

        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->hasNotified(CreditAlertThreshold::Warning));
        $this->assertTrue($reloaded->hasNotified(CreditAlertThreshold::High));
    }

    public function testASecondPassDoesNotAlertAgain(): void
    {
        $creditPurchase = $this->validatedPurchase(10.0);
        $this->consume($creditPurchase, 8.5);

        $this->check();
        $response = $this->check();

        $this->assertSame(0, $response->alerted);
        $this->assertSame([CreditAlertThreshold::Warning], $this->thresholdEvents());
    }

    public function testANewPurchaseRestartsTheAlertCycle(): void
    {
        $creditPurchase = $this->validatedPurchase(10.0, new \DateTimeImmutable('-2 days'));
        $this->consume($creditPurchase, 10.0);

        $this->check();

        $this->validatedPurchase(10.0, new \DateTimeImmutable('-1 day'));

        $response = $this->check();

        $this->assertSame(0, $response->alerted);
        $this->assertSame([CreditAlertThreshold::Depleted], $this->thresholdEvents());
    }

    public function testAllocationsOfExpiredContributionsDoNotCount(): void
    {
        $creditPurchase = $this->validatedPurchase(10.0);

        $expired = ProjectContributionMother::expired($this->accountId, $this->projectId, 9.0);
        $this->allocate($expired, $creditPurchase, 9.0);

        $response = $this->check();

        $this->assertSame(1, $response->evaluated);
        $this->assertSame(0, $response->alerted);
    }

    public function testTheFullSweepEvaluatesTheLatestPurchaseOfEveryAccountAndProject(): void
    {
        $creditPurchase = $this->validatedPurchase(10.0);
        $this->consume($creditPurchase, 9.5);

        $otherPurchase = CreditPurchaseMother::validatedAt(
            new \DateTimeImmutable('-1 day'),
            AccountId::generate(),
            ProjectId::generate(),
            10.0,
        );
        $this->creditPurchaseRepository->add($otherPurchase);

        $response = $this->service->__invoke(new CheckCreditAlertsRequest(now: new \DateTimeImmutable('NOW')), $this->user);

        $this->assertSame(2, $response->evaluated);
        $this->assertSame([CreditAlertThreshold::High], $this->thresholdEvents());
    }

    private function check(): CheckCreditAlertsResponse
    {
        return $this->service->__invoke(
            new CheckCreditAlertsRequest(
                accountId: $this->accountId,
                projectId: $this->projectId,
                now: new \DateTimeImmutable('NOW'),
            ),
            $this->user,
        );
    }

    public function testThresholdsOfAReceivedLotAreMeasuredOnItsEffectiveTonnes(): void
    {
        $lot = CreditPurchaseMother::receivedLot(
            reference: 'SERIAL-1',
            purchasedTonnes: 100.0,
            offsetTonnes: 30.0,
            lotNumber: 2,
            accountId: $this->accountId,
            projectId: $this->projectId,
        );
        $this->creditPurchaseRepository->add($lot);

        $this->consume($lot, 63.0);

        $response = $this->check();

        $this->assertSame(1, $response->alerted);
        $this->assertSame([CreditAlertThreshold::High], $this->thresholdEvents());

        $event = $this->lastThresholdEvent();

        $this->assertSame(70.0, $event->purchasedTonnes());
        $this->assertSame(63.0, $event->consumedTonnes());
        $this->assertSame(7.0, $event->availableTonnes());
    }

    public function testThresholdsOfALotReceivedBelowTheTopOfTheSerialAreMeasuredOnItsOwnRange(): void
    {
        $lot = CreditPurchaseMother::receivedLot(
            reference: 'SERIAL-1',
            purchasedTonnes: 100.0,
            offsetTonnes: 40.0,
            upperOffsetTonnes: 40.0,
            lotNumber: 3,
            accountId: $this->accountId,
            projectId: $this->projectId,
        );
        $this->creditPurchaseRepository->add($lot);

        $this->consume($lot, 16.0);

        $response = $this->check();

        $this->assertSame(1, $response->alerted);
        $this->assertSame([CreditAlertThreshold::Warning], $this->thresholdEvents());

        $event = $this->lastThresholdEvent();

        $this->assertSame(20.0, $event->purchasedTonnes());
        $this->assertSame(16.0, $event->consumedTonnes());
        $this->assertSame(4.0, $event->availableTonnes());
    }

    private function lastThresholdEvent(): CreditThresholdReached
    {
        $events = array_values(array_filter(
            $this->spySubscriber->events(),
            static fn(DomainEvent $event): bool => $event instanceof CreditThresholdReached,
        ));

        $event = end($events);

        $this->assertInstanceOf(CreditThresholdReached::class, $event);

        return $event;
    }

    private function validatedPurchase(float $tonnes, ?\DateTimeImmutable $resolvedAt = null): CreditPurchase
    {
        $creditPurchase = CreditPurchaseMother::validatedAt(
            $resolvedAt ?? new \DateTimeImmutable('-1 day'),
            $this->accountId,
            $this->projectId,
            $tonnes,
        );

        $this->creditPurchaseRepository->add($creditPurchase);

        return $creditPurchase;
    }

    private function consume(CreditPurchase $creditPurchase, float $tonnes): void
    {
        $this->allocate(
            ProjectContributionMother::paid($this->accountId, $this->projectId, $tonnes),
            $creditPurchase,
            $tonnes,
        );
    }

    private function allocate(ProjectContribution $projectContribution, CreditPurchase $creditPurchase, float $tonnes): void
    {
        $projectContribution->allocateCredits([
            new AllocatedCreditLine(
                position: 0,
                creditPurchaseId: $creditPurchase->id(),
                purchaseReference: $creditPurchase->reference(),
                tonnes: $tonnes,
                startTonnes: 0.0,
            ),
        ]);

        $this->projectContributionRepository->add($projectContribution);
    }

    /** @return list<CreditAlertThreshold> */
    private function thresholdEvents(): array
    {
        return array_values(array_map(
            static fn(CreditThresholdReached $event): CreditAlertThreshold => $event->threshold(),
            array_filter(
                $this->spySubscriber->events(),
                static fn(DomainEvent $event): bool => $event instanceof CreditThresholdReached,
            ),
        ));
    }
}
