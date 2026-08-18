<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Compensate;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Application\ProjectContribution\Compensate\CompensateWithOwnCreditRequest;
use App\Balance\Application\ProjectContribution\Compensate\CompensateWithOwnCreditService;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Application\ProjectContribution\Quote\QuoteOwnerCompensationService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\InsufficientProjectCreditException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFunding;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaid;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRegistered;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountProjectRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ProjectContribution\FakeCreditReservationLock;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CompensateWithOwnCreditServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private User $owner;
    private InMemoryProjectRepository $projects;
    private InMemoryAccountProjectRepository $accountProjects;
    private InMemoryCreditPurchaseRepository $creditPurchases;
    private InMemoryProjectContributionRepository $contributions;
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->accountId);
        $this->projects = ProjectRepositoryMother::inMemory();
        $this->accountProjects = AccountProjectRepositoryMother::inMemory();
        $this->creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->spy = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testTheContributionIsPaidWithoutPaymentNorExpiration(): void
    {
        $this->givenAdheredProjectWithOwnCredit(10.0);

        $response = $this->createSUT()($this->request(2000.0), $this->owner);

        $contribution = $this->contributions->find($response->projectContributionId);

        $this->assertNotNull($contribution);
        $this->assertTrue($contribution->isPaid());
        $this->assertNull($contribution->paymentId());
        $this->assertNull($contribution->expiresAt());
        $this->assertSame(ProjectContributionFunding::OwnCredit, $contribution->funding());
    }

    public function testNothingIsCharged(): void
    {
        $this->givenAdheredProjectWithOwnCredit(10.0);

        $response = $this->createSUT()($this->request(2000.0), $this->owner);

        $contribution = $this->contributions->find($response->projectContributionId);

        $this->assertNotNull($contribution);
        $this->assertSame(0.0, $contribution->totalAmount());
        $this->assertSame(0.0, $contribution->managementFeeAmount());
        $this->assertSame(0.0, $contribution->vatAmount());
        $this->assertSame(30.0, $contribution->contributionAmount());
        $this->assertSame(2.0, $contribution->tonnes());
    }

    public function testItPublishesTheRegistrationAndThePayment(): void
    {
        $this->givenAdheredProjectWithOwnCredit(10.0);

        $this->createSUT()($this->request(2000.0), $this->owner);

        $published = array_map(
            static fn(DomainEvent $event): string => $event::class,
            $this->spy->events(),
        );

        $this->assertContains(ProjectContributionRegistered::class, $published);
        $this->assertContains(ProjectContributionPaid::class, $published);
    }

    public function testItConsumesTheOwnLots(): void
    {
        $ownPurchase = $this->givenAdheredProjectWithOwnCredit(10.0);

        $response = $this->createSUT()($this->request(2000.0), $this->owner);

        $contribution = $this->contributions->find($response->projectContributionId);

        $this->assertNotNull($contribution);

        $allocations = $contribution->creditAllocations();

        $this->assertCount(1, $allocations);
        $this->assertTrue($ownPurchase->id()->equals($allocations[0]->creditPurchaseId()));
        $this->assertSame(2.0, $allocations[0]->tonnes());
    }

    public function testItRefusesToCompensateMoreThanTheOwnCredit(): void
    {
        $this->givenAdheredProjectWithOwnCredit(1.0);

        $this->expectException(InsufficientProjectCreditException::class);

        $this->createSUT()($this->request(2000.0), $this->owner);
    }

    public function testAnOwnerOfAnotherAccountIsDenied(): void
    {
        $this->givenAdheredProjectWithOwnCredit(10.0);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(
            $this->request(2000.0),
            UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, AccountId::generate()),
        );
    }

    private function request(float $co2eqKg): CompensateWithOwnCreditRequest
    {
        return new CompensateWithOwnCreditRequest(
            projectContributionId: ProjectContributionId::generate(),
            accountId: $this->accountId,
            projectId: $this->projectId,
            co2eqKg: $co2eqKg,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
            originId: (string) ProjectContributionId::generate(),
            originName: 'Congreso Andalucía Sostenible',
            beneficiaryName: 'Hoteles del Sur SL',
            beneficiaryEmail: 'owner@example.com',
            beneficiaryCountry: 'es',
            beneficiaryIsCompany: true,
            beneficiaryCompanyName: 'Hoteles del Sur SL',
            beneficiaryVatNumber: 'B12345678',
        );
    }

    private function givenAdheredProjectWithOwnCredit(float $tonnes): CreditPurchase
    {
        $this->projects->add(ProjectMother::contributable(id: $this->projectId));
        $this->accountProjects->add(AccountProjectMother::from($this->accountId, $this->projectId));

        $purchase = CreditPurchaseMother::validated(
            $this->accountId,
            $this->projectId,
            tonnes: $tonnes,
            paymentType: CreditPurchasePaymentType::PaidByClient,
        );
        $this->creditPurchases->add($purchase);

        return $purchase;
    }

    private function createSUT(): CompensateWithOwnCreditService
    {
        return new CompensateWithOwnCreditService(
            new QuoteOwnerCompensationService(
                $this->projects,
                $this->accountProjects,
                new CreditAvailability($this->creditPurchases, $this->contributions),
            ),
            $this->contributions,
            new AllocateContributionCreditsService(
                $this->contributions,
                $this->creditPurchases,
                $this->projects,
                new FakeCreditReservationLock(),
            ),
            new FakeCreditReservationLock(),
            new ProjectContributionAuthorizer(),
        );
    }
}
