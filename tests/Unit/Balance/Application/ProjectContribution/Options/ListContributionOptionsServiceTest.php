<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Options;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountProjectRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class ListContributionOptionsServiceTest extends TestCase
{
    private AccountId $accountId;
    private InMemoryProjectRepository $projects;
    private InMemoryAccountProjectRepository $accountProjects;
    private InMemoryCreditPurchaseRepository $creditPurchases;
    private InMemoryProjectContributionRepository $contributions;

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->projects = ProjectRepositoryMother::inMemory();
        $this->accountProjects = AccountProjectRepositoryMother::inMemory();
        $this->creditPurchases = CreditPurchaseRepositoryMother::inMemory();
        $this->contributions = ProjectContributionRepositoryMother::inMemory();
    }

    public function testListsTheAdheredProjectsWithCreditLeft(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 10.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId));

        $this->assertFalse($response->isEmpty());
        $this->assertCount(1, $response->options);

        $option = $response->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertSame(20.0, $option->pricePerTonne);
        $this->assertSame(15.0, $option->costPerTonne);
        $this->assertSame(10.0, $option->availableTonnes);
        $this->assertTrue($option->limitedByCredit);
    }

    public function testSuggestsTheAmountForTheEmissionsToOffset(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 10.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0));

        $option = $response->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertSame(40.0, $option->suggestedAmount);
    }

    public function testWithoutEmissionsThereIsNoSuggestedAmount(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 10.0);

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertNull($option->suggestedAmount);
    }

    public function testDiscardsAnOffsettingProjectWithoutEnoughCreditForTheEmissions(): void
    {
        $this->adheredProject(purchasedTonnes: 1.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0));

        $this->assertTrue($response->isEmpty());
    }

    public function testDiscardsAnOffsettingProjectWithoutAnyCreditLeft(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 10.0);
        $this->contributions->add(
            ProjectContributionMother::paid($this->accountId, $project->id(), tonnes: 10.0),
        );

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId));

        $this->assertTrue($response->isEmpty());
    }

    public function testASocialProjectIsListedWithoutCredit(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 0.0, projectType: ProjectType::SocialProject);

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertFalse($option->limitedByCredit);
        $this->assertSame(\INF, $option->availableTonnes);
        $this->assertSame(15.0, $option->costPerTonne);
    }

    public function testDiscardsProjectsWithoutACost(): void
    {
        $this->adheredProject(purchasedTonnes: 10.0, costPerTonne: null);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId));

        $this->assertTrue($response->isEmpty());
    }

    public function testDiscardsProjectsWithoutAPrice(): void
    {
        $this->adheredProject(purchasedTonnes: 10.0, pricePerTonne: 0.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId));

        $this->assertTrue($response->isEmpty());
    }

    public function testDiscardsProjectsTheAccountHasNotAdhered(): void
    {
        $project = ProjectMother::contributable();
        $this->projects->add($project);
        $this->creditPurchases->add(
            CreditPurchaseMother::validated($this->accountId, $project->id(), tonnes: 10.0),
        );

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId));

        $this->assertTrue($response->isEmpty());
    }

    public function testDiscardsProjectsOfAnotherAccount(): void
    {
        $this->adheredProject(purchasedTonnes: 10.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest(AccountId::generate()));

        $this->assertTrue($response->isEmpty());
    }

    private function adheredProject(
        float $purchasedTonnes,
        ProjectType $projectType = ProjectType::EmissionCompensation,
        float $pricePerTonne = 20.0,
        ?float $costPerTonne = 15.0,
    ): Project {
        $projectId = ProjectId::generate();

        $this->projects->add(ProjectMother::contributable(
            projectType: $projectType,
            pricePerTonne: $pricePerTonne,
            costPerTonne: $costPerTonne,
            id: $projectId,
        ));

        $this->accountProjects->add(AccountProjectMother::from($this->accountId, $projectId));

        if ($purchasedTonnes > 0.0) {
            $this->creditPurchases->add(
                CreditPurchaseMother::validated($this->accountId, $projectId, tonnes: $purchasedTonnes),
            );
        }

        $project = $this->projects->find($projectId);

        $this->assertNotNull($project);

        return $project;
    }

    private function createSUT(): ListContributionOptionsService
    {
        return new ListContributionOptionsService(
            $this->accountProjects,
            $this->projects,
            new CreditAvailability($this->creditPurchases, $this->contributions),
        );
    }
}
