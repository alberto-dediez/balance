<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Reallocate;

use App\Balance\Application\ProjectContribution\Reallocate\ReallocateAllUnderallocatedContributionsRequest;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateAllUnderallocatedContributionsResponse;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateAllUnderallocatedContributionsService;
use App\Balance\Application\ProjectContribution\Reallocate\ReallocateUnderallocatedContributionsService;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
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
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReallocateAllUnderallocatedContributionsServiceTest extends TestCase
{
    private InMemoryProjectContributionRepository $contributions;
    private InMemoryCreditPurchaseRepository $purchases;
    private InMemoryProjectRepository $projects;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->contributions = ProjectContributionRepositoryMother::inMemory();
        $this->purchases = CreditPurchaseRepositoryMother::inMemory();
        $this->projects = ProjectRepositoryMother::inMemory();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testTheSweepCoversEveryAccountAndProjectPair(): void
    {
        $firstAccountId = AccountId::generate();
        $secondAccountId = AccountId::generate();
        $projectId = $this->givenCompensationProject();
        $otherProjectId = $this->givenCompensationProject();

        $this->givenValidatedPurchase($firstAccountId, $projectId, 10.0);
        $this->givenValidatedPurchase($secondAccountId, $otherProjectId, 10.0);

        $first = $this->givenPaidContribution($firstAccountId, $projectId, 5.0);
        $second = $this->givenPaidContribution($secondAccountId, $otherProjectId, 3.0);

        $response = $this->reallocate();

        $this->assertSame(2, $response->pairsProcessed);
        $this->assertSame(2, $response->contributionsCovered);
        $this->assertSame(8.0, $response->reallocatedTonnes);

        $this->assertSame(5.0, $first->allocatedTonnes());
        $this->assertSame(3.0, $second->allocatedTonnes());
    }

    public function testTheSweepCanBeScopedToOneAccount(): void
    {
        $accountId = AccountId::generate();
        $otherAccountId = AccountId::generate();
        $projectId = $this->givenCompensationProject();

        $this->givenValidatedPurchase($accountId, $projectId, 10.0);
        $this->givenValidatedPurchase($otherAccountId, $projectId, 10.0);

        $scoped = $this->givenPaidContribution($accountId, $projectId, 5.0);
        $ignored = $this->givenPaidContribution($otherAccountId, $projectId, 3.0);

        $response = $this->reallocate(accountId: $accountId);

        $this->assertSame(1, $response->pairsProcessed);
        $this->assertSame(5.0, $response->reallocatedTonnes);
        $this->assertSame(5.0, $scoped->allocatedTonnes());
        $this->assertSame(0.0, $ignored->allocatedTonnes());
    }

    public function testTheSweepCanBeScopedToOneProject(): void
    {
        $accountId = AccountId::generate();
        $projectId = $this->givenCompensationProject();
        $otherProjectId = $this->givenCompensationProject();

        $this->givenValidatedPurchase($accountId, $projectId, 10.0);
        $this->givenValidatedPurchase($accountId, $otherProjectId, 10.0);

        $scoped = $this->givenPaidContribution($accountId, $projectId, 5.0);
        $ignored = $this->givenPaidContribution($accountId, $otherProjectId, 3.0);

        $response = $this->reallocate(projectId: $projectId);

        $this->assertSame(1, $response->pairsProcessed);
        $this->assertSame(5.0, $scoped->allocatedTonnes());
        $this->assertSame(0.0, $ignored->allocatedTonnes());
    }

    public function testNothingHappensWithoutUnderallocatedContributions(): void
    {
        $accountId = AccountId::generate();
        $projectId = $this->givenCompensationProject();

        $this->givenValidatedPurchase($accountId, $projectId, 10.0);

        $contribution = $this->givenPaidContribution($accountId, $projectId, 5.0);

        $this->reallocate();

        $response = $this->reallocate();

        $this->assertSame(0, $response->pairsProcessed);
        $this->assertSame(0, $response->contributionsCovered);
        $this->assertSame(0.0, $response->reallocatedTonnes);
        $this->assertSame(5.0, $contribution->allocatedTonnes());
    }

    private function givenCompensationProject(): ProjectId
    {
        $projectId = ProjectId::generate();

        $this->projects->add(ProjectMother::contributable(
            projectType: ProjectType::EmissionCompensation,
            id: $projectId,
        ));

        return $projectId;
    }

    private function givenValidatedPurchase(AccountId $accountId, ProjectId $projectId, float $tonnes): void
    {
        $this->purchases->add(CreditPurchaseMother::validatedAt(
            new \DateTimeImmutable('-1 day'),
            $accountId,
            $projectId,
            $tonnes,
        ));
    }

    private function givenPaidContribution(AccountId $accountId, ProjectId $projectId, float $tonnes): ProjectContribution
    {
        $contribution = ProjectContributionMother::paid($accountId, $projectId, $tonnes);

        $this->contributions->add($contribution);

        return $contribution;
    }

    private function reallocate(
        ?AccountId $accountId = null,
        ?ProjectId $projectId = null,
    ): ReallocateAllUnderallocatedContributionsResponse {
        return (new ReallocateAllUnderallocatedContributionsService(
            $this->contributions,
            new ReallocateUnderallocatedContributionsService(
                $this->contributions,
                $this->purchases,
                $this->projects,
                new FakeCreditReservationLock(),
            ),
        ))(new ReallocateAllUnderallocatedContributionsRequest($accountId, $projectId), UserMother::adminUser());
    }
}
