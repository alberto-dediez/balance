<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Reallocate;

use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsRequest;
use App\Balance\Application\ProjectContribution\Allocate\AllocateContributionCreditsService;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsRequest;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsResponse;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditsReallocated;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ProjectContribution\FakeCreditReservationLock;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ReallocateUnderallocatedContributionsServiceTest extends TestCase
{
    private AccountId $accountId;
    private ProjectId $projectId;
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryCreditPurchaseRepository $purchases;
    private InMemoryProjectRepository $projects;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = AccountId::generate();
        $this->projectId = ProjectId::generate();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->purchases = CreditPurchaseRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAnUnderallocatedContributionIsReallocatedWhenNewCreditsBecomeAvailable(): void
    {
        $this->givenCompensationProject();

        $this->givenValidatedPurchase(2.0, '-3 days');
        $contribution = $this->givenPaidContribution(5.0);
        $this->allocate($contribution);

        $this->assertSame(2.0, $contribution->allocatedTonnes());
        $this->assertSame(3.0, $contribution->unallocatedTonnes());

        $newPurchase = $this->givenValidatedPurchase(10.0, '-1 day');

        $spy = new SpyDomainEventSubscriber();

        $response = $this->reallocate();

        $this->assertSame(1, $response->contributionsCovered);
        $this->assertSame(3.0, $response->reallocatedTonnes);

        $allocations = $contribution->creditAllocations();
        $this->assertCount(2, $allocations);
        $this->assertTrue($newPurchase->id()->equals($allocations[1]->creditPurchaseId()));
        $this->assertSame(3.0, $allocations[1]->tonnes());
        $this->assertSame(5.0, $contribution->allocatedTonnes());

        $this->assertInstanceOf(ProjectContributionCreditsReallocated::class, $spy->lastEvent());
    }

    public function testNothingIsReallocatedWhenAllContributionsAreFullyAllocated(): void
    {
        $this->givenCompensationProject();

        $this->givenValidatedPurchase(5.0, '-1 day');
        $contribution = $this->givenPaidContribution(5.0);
        $this->allocate($contribution);

        $this->assertSame(0.0, $contribution->unallocatedTonnes());

        $response = $this->reallocate();

        $this->assertSame(0, $response->contributionsCovered);
        $this->assertSame(0.0, $response->reallocatedTonnes);
    }

    public function testNothingIsReallocatedWhenThereAreNoPaidContributions(): void
    {
        $this->givenCompensationProject();

        $this->givenValidatedPurchase(10.0, '-1 day');

        $response = $this->reallocate();

        $this->assertSame(0, $response->contributionsCovered);
        $this->assertSame(0.0, $response->reallocatedTonnes);
    }

    public function testAnImportedContributionReceivesNoCredits(): void
    {
        $this->givenCompensationProject();

        $this->givenValidatedPurchase(10.0, '-1 day');
        $contribution = ProjectContributionMother::imported($this->accountId, $this->projectId, 3.0);
        $this->contributions->add($contribution);

        $response = $this->reallocate();

        $this->assertSame(0, $response->contributionsCovered);
        $this->assertSame(0.0, $response->reallocatedTonnes);
        $this->assertSame(0.0, $contribution->allocatedTonnes());
    }

    public function testMultipleUnderallocatedContributionsAreReallocatedTogether(): void
    {
        $this->givenCompensationProject();

        $this->givenValidatedPurchase(10.0, '-1 day');

        $first = $this->givenPaidContribution(5.0);
        $second = $this->givenPaidContribution(3.0);

        $response = $this->reallocate();

        $this->assertSame(2, $response->contributionsCovered);
        $this->assertSame(8.0, $response->reallocatedTonnes);

        $this->assertSame(5.0, $first->allocatedTonnes());
        $this->assertSame(3.0, $second->allocatedTonnes());
    }

    public function testAlreadyAllocatedTonnesAreRespectedAndNotDoubleCounted(): void
    {
        $this->givenCompensationProject();

        $purchase = $this->givenValidatedPurchase(10.0, '-3 days');

        $fullyAllocated = $this->givenPaidContribution(4.0);
        $this->allocate($fullyAllocated);

        $this->assertSame(4.0, $this->contributions->allocatedTonnesByPurchase($this->accountId, $this->projectId)[(string) $purchase->id()]);

        $underallocated = $this->givenPaidContribution(5.0);

        $response = $this->reallocate();

        $this->assertSame(1, $response->contributionsCovered);
        $this->assertSame(5.0, $response->reallocatedTonnes);

        $this->assertSame(5.0, $underallocated->allocatedTonnes());
        $this->assertSame(
            [['serial' => $purchase->reference(), 'startTonnes' => 4.0, 'endTonnes' => 9.0]],
            $underallocated->creditLots(),
        );
    }

    public function testASocialProjectReallocatesNothing(): void
    {
        $this->projects->add(ProjectMother::contributable(projectType: ProjectType::SocialProject, id: $this->projectId));

        $this->givenValidatedPurchase(10.0, '-1 day');
        $this->givenPaidContribution(2.0);

        $response = $this->reallocate();

        $this->assertSame(0, $response->contributionsCovered);
        $this->assertSame(0.0, $response->reallocatedTonnes);
    }

    public function testAMissingProjectReallocatesNothing(): void
    {
        $this->givenValidatedPurchase(10.0, '-1 day');
        $this->givenPaidContribution(2.0);

        $response = $this->reallocate();

        $this->assertSame(0, $response->contributionsCovered);
        $this->assertSame(0.0, $response->reallocatedTonnes);
    }

    private function givenCompensationProject(): void
    {
        $this->projects->add(ProjectMother::contributable(
            projectType: ProjectType::EmissionCompensation,
            id: $this->projectId,
        ));
    }

    private function givenValidatedPurchase(float $tonnes, string $resolvedAt): CreditPurchase
    {
        $purchase = CreditPurchaseMother::validatedAt(
            new \DateTimeImmutable($resolvedAt),
            $this->accountId,
            $this->projectId,
            $tonnes,
        );

        $this->purchases->add($purchase);

        return $purchase;
    }

    private function givenPaidContribution(float $tonnes): ProjectContribution
    {
        $contribution = ProjectContributionMother::paid($this->accountId, $this->projectId, $tonnes);

        $this->contributions->add($contribution);

        return $contribution;
    }

    private function allocate(ProjectContribution $contribution): void
    {
        (new AllocateContributionCreditsService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            new FakeCreditReservationLock(),
        ))(new AllocateContributionCreditsRequest($contribution->id()));
    }

    private function reallocate(): ReallocateUnderallocatedContributionsResponse
    {
        return (new ReallocateUnderallocatedContributionsService(
            $this->contributions,
            $this->purchases,
            $this->projects,
            new FakeCreditReservationLock(),
        ))(new ReallocateUnderallocatedContributionsRequest(
            accountId: $this->accountId,
            projectId: $this->projectId,
        ));
    }
}
