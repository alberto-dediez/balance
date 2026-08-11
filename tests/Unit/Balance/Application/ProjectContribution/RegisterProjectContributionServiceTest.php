<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionService;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionService;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ContributionAmountTooLowException;
use App\Balance\Domain\Model\ProjectContribution\ContributionCalculator;
use App\Balance\Domain\Model\ProjectContribution\InsufficientProjectCreditException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Payments\Application\Payment\Start\StartPaymentService;
use App\Payments\Domain\Model\Payment\PaymentGateway;
use App\Payments\Domain\Model\Payment\PaymentGatewayException;
use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use App\Payments\Infrastructure\Payment\Fake\FakePaymentGateway;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Send\SpyDeferredCommandHandler;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use App\Tests\Unit\Security\Application\User\SystemActor\SystemActorResolverMother;
use PHPUnit\Framework\TestCase;

class RegisterProjectContributionServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryProjectContributionRepository $contributions;
    private SystemActorResolver $systemActor;
    private SpyDeferredCommandHandler $deferredCommands;
    private FakePaymentGateway $gateway;
    private FakeCreditReservationLock $lock;

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        CommandBusDispatcher::instance()->reset();

        $this->systemActor = SystemActorResolverMother::withPaymentUser();
        $this->deferredCommands = new SpyDeferredCommandHandler();
        $this->deferredCommands->register();
        $this->gateway = new FakePaymentGateway();
        $this->lock = new FakeCreditReservationLock();
    }

    protected function tearDown(): void
    {
        CommandBusDispatcher::instance()->reset();
    }

    public function testRegistersTheContributionAndStartsThePayment(): void
    {
        $sut = $this->createSUT();

        $response = $sut($this->request());

        $contribution = $this->contributions->find($response->projectContributionId);

        $this->assertNotNull($contribution);
        $this->assertSame(ProjectContributionStatus::Pending, $contribution->status());
        $this->assertSame(2.0, $contribution->tonnes());
        $this->assertSame(40.0, $contribution->taxableAmount());
        $this->assertSame(10.0, $contribution->managementFeeAmount());
        $this->assertSame(30.0, $contribution->contributionAmount());
        $this->assertSame(48.4, $contribution->totalAmount());
        $this->assertSame(VatExemptionReason::Standard, $contribution->vatReason());
        $this->assertStringStartsWith('https://checkout.stripe.test/', $response->redirectUrl);
    }

    /** Kept from the request so the certificate is not left to the language of whoever confirms the payment. */
    public function testTheLanguageOfTheGuestIsKeptWithTheContribution(): void
    {
        $sut = $this->createSUT();

        $response = $sut($this->request(locale: Locale::English));

        $this->assertSame(Locale::English, $this->contributions->find($response->projectContributionId)?->locale());
    }

    public function testTheReservationIsScheduledForExpiration(): void
    {
        $now = new \DateTimeImmutable('2026-08-06 12:00:00');

        $response = $this->createSUT()($this->request(now: $now));

        $contribution = $this->contributions->find($response->projectContributionId);

        $this->assertNotNull($contribution);
        $this->assertEquals($now->modify('+12 hours'), $contribution->expiresAt());
        $deferred = $this->deferredCommands->commands();

        $this->assertCount(1, $deferred);
        $this->assertInstanceOf(ExpireProjectContributionRequest::class, $deferred[0]->command());
        $this->assertEquals($now->modify('+12 hours'), $deferred[0]->executeAt());
    }

    public function testThePaymentCarriesTheContributionAsItsSubject(): void
    {
        $response = $this->createSUT()($this->request());

        $checkout = $this->gateway->lastCheckout();

        $this->assertNotNull($checkout);
        $this->assertSame(48.4, $checkout->amount);
        $this->assertSame(4840, $checkout->amountInCents());
        $this->assertSame(RegisterProjectContributionService::SUBJECT_TYPE, $checkout->metadata['subject_type']);
        $this->assertSame((string) $response->projectContributionId, $checkout->metadata['subject_id']);
    }

    public function testAFreeAmountIsConvertedIntoTonnes(): void
    {
        $response = $this->createSUT()($this->request(co2eqKg: null, freeAmount: 50.0));

        $contribution = $this->contributions->find($response->projectContributionId);

        $this->assertNotNull($contribution);
        $this->assertTrue($contribution->isFreeAmount());
        $this->assertNull($contribution->co2eqKg());
        $this->assertSame(2.5, $contribution->tonnes());
        $this->assertSame(50.0, $contribution->taxableAmount());
    }

    public function testRejectsAnOffsettingProjectWithoutEnoughCredit(): void
    {
        $sut = $this->createSUT(purchasedTonnes: 1.0);

        $this->expectException(InsufficientProjectCreditException::class);

        $sut($this->request());
    }

    public function testRejectsAnAmountTheGatewayWouldNotCharge(): void
    {
        $sut = $this->createSUT();

        $this->expectException(ContributionAmountTooLowException::class);

        $sut($this->request(co2eqKg: null, freeAmount: 0.2));
    }

    public function testAnAmountBelowTheMinimumHoldsNoCreditAndOpensNoPayment(): void
    {
        $sut = $this->createSUT();

        try {
            $sut($this->request(co2eqKg: null, freeAmount: 0.2));
        } catch (ContributionAmountTooLowException) {
        }

        $this->assertCount(0, $this->contributions->findByFilter(new ProjectContributionFilter()));
        $this->assertCount(0, $this->gateway->checkouts());
    }

    public function testLiveReservationsOfOtherGuestsCountAgainstTheAvailableCredit(): void
    {
        $this->contributions->add(
            ProjectContributionMother::pending($this->accountId, $this->projectId, tonnes: 9.0),
        );

        $sut = $this->createSUT(purchasedTonnes: 10.0);

        $this->expectException(InsufficientProjectCreditException::class);

        $sut($this->request());
    }

    public function testASocialProjectIsNotLimitedByCredit(): void
    {
        $sut = $this->createSUT(projectType: ProjectType::SocialProject, costPerTonne: 20.0, purchasedTonnes: 0.0);

        $response = $sut($this->request());

        $contribution = $this->contributions->find($response->projectContributionId);

        $this->assertNotNull($contribution);
        $this->assertSame(0.0, $contribution->managementFeeAmount());
        $this->assertSame(40.0, $contribution->contributionAmount());
        $this->assertSame(20.0, $contribution->costPerTonne());
    }

    public function testTheHoldIsWrittenBeforeTheLockIsReleased(): void
    {
        $heldWhileLocked = null;

        $this->lock = new FakeCreditReservationLock(function () use (&$heldWhileLocked): void {
            $heldWhileLocked = $this->contributions->findByFilter(new ProjectContributionFilter());
        });

        $this->createSUT()($this->request());

        $this->assertNotNull($heldWhileLocked);
        $this->assertCount(1, $heldWhileLocked);
        $this->assertSame(2.0, $heldWhileLocked[0]->tonnes());
        $this->assertEquals([[$this->accountId, $this->projectId]], $this->lock->locks());
    }

    public function testTheCreditIsNotHeldWhenTheAvailabilityCheckFails(): void
    {
        $sut = $this->createSUT(purchasedTonnes: 1.0);

        try {
            $sut($this->request());
        } catch (InsufficientProjectCreditException) {
        }

        $this->assertCount(0, $this->contributions->findByFilter(new ProjectContributionFilter()));
        $this->assertCount(0, $this->gateway->checkouts());
    }

    public function testAFailedPaymentStartReleasesTheHeldCredit(): void
    {
        $request = $this->request();
        $sut = $this->createSUT(gateway: new FailingPaymentGateway());

        try {
            $sut($request);
            $this->fail('The gateway failure should not be swallowed.');
        } catch (PaymentGatewayException) {
        }

        $contribution = $this->contributions->find($request->projectContributionId);

        $this->assertNotNull($contribution);
        $this->assertSame(ProjectContributionStatus::Failed, $contribution->status());
        $this->assertSame(0.0, $this->contributions->committedTonnes($this->accountId, $this->projectId, new \DateTimeImmutable('NOW')));
        $this->assertCount(0, $this->deferredCommands->commands());
    }

    public function testRejectsAProjectTheAccountHasNotAdhered(): void
    {
        $sut = $this->createSUT(adhered: false);

        $this->expectException(ProjectNotContributableException::class);

        $sut($this->request());
    }

    public function testRejectsAProjectWithoutAPrice(): void
    {
        $sut = $this->createSUT(pricePerTonne: null);

        $this->expectException(ProjectNotContributableException::class);

        $sut($this->request());
    }

    private function request(
        ?float $co2eqKg = 2000.0,
        ?float $freeAmount = null,
        ?\DateTimeImmutable $now = null,
        ?Locale $locale = null,
    ): RegisterProjectContributionRequest {
        return new RegisterProjectContributionRequest(
            projectContributionId: ProjectContributionId::generate(),
            accountId: $this->accountId,
            projectId: $this->projectId,
            contributorName: 'Guest',
            contributorEmail: 'guest@example.com',
            contributorCountry: 'ES',
            successUrl: 'https://app.test/return',
            cancelUrl: 'https://app.test/cancel',
            originId: 'trip-1',
            co2eqKg: $co2eqKg,
            freeAmount: $freeAmount,
            locale: $locale,
            now: $now,
        );
    }

    private function createSUT(
        ProjectType $projectType = ProjectType::EmissionCompensation,
        ?float $pricePerTonne = 20.0,
        ?float $costPerTonne = 15.0,
        float $purchasedTonnes = 10.0,
        bool $adhered = true,
        ?PaymentGateway $gateway = null,
    ): RegisterProjectContributionService {
        $projects = ProjectRepositoryMother::inMemory();
        $projects->add($this->project($projectType, $pricePerTonne, $costPerTonne));

        $accountProjects = AccountProjectRepositoryMother::inMemory();

        if ($adhered) {
            $accountProjects->add(AccountProjectMother::from($this->accountId, $this->projectId));
        }

        $creditPurchases = CreditPurchaseRepositoryMother::inMemory();

        if ($purchasedTonnes > 0.0) {
            $creditPurchases->add(
                CreditPurchaseMother::validated($this->accountId, $this->projectId, tonnes: $purchasedTonnes),
            );
        }

        $creditAvailability = new CreditAvailability($creditPurchases, $this->contributions);

        return new RegisterProjectContributionService(
            new QuoteContributionService(
                $projects,
                $accountProjects,
                $creditAvailability,
                new ContributionCalculator(new VatCalculator(
                    new VatNumberFormatValidator(),
                    rates: ['ES' => 21.0],
                    euCountries: ['ES'],
                    homeCountry: 'ES',
                )),
            ),
            $this->contributions,
            new StartPaymentService(PaymentRepositoryMother::inMemory(), $gateway ?? $this->gateway),
            $this->systemActor,
            $this->lock,
            reservationHours: 12,
        );
    }

    private function project(ProjectType $projectType, ?float $pricePerTonne, ?float $costPerTonne): Project
    {
        return ProjectMother::from(
            name: 'Reforestación',
            id: (string) $this->projectId,
            projectType: $projectType,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $costPerTonne,
        );
    }
}
