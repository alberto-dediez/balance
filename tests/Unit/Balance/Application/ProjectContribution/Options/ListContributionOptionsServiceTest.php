<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\Options;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFunding;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountProjectRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Application\CreditPurchase\Balance\CreditSourceSelectorMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class ListContributionOptionsServiceTest extends TestCase
{
    private AccountId $accountId;
    private InMemoryProjectRepository $projects;
    private InMemoryAccountProjectRepository $accountProjects;
    private InMemoryCreditPurchaseRepository $creditPurchases;
    private InMemoryProjectContributionRepository $contributions;
    private Account $distributor;

    /** @var list<DistributorAssignment> */
    private array $assignments = [];

    /** @var list<Property> */
    private array $properties = [];

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', (string) AccountId::generate());
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

    public function testInOwnerModeAnOffsettingProjectWithOwnLotsIsFundedWithOwnCredit(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 10.0);

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, ownerMode: true))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertSame(ProjectContributionFunding::OwnCredit, $option->funding);
        $this->assertSame(10.0, $option->ownAvailableTonnes);
        $this->assertSame(10.0, $option->availableTonnes);
    }

    public function testInOwnerModeAProjectWithOnlyBioscoreLotsIsStillPaid(): void
    {
        $project = $this->adheredProject(
            purchasedTonnes: 10.0,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
        );

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, ownerMode: true))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertSame(ProjectContributionFunding::Payment, $option->funding);
        $this->assertSame(0.0, $option->ownAvailableTonnes);
    }

    public function testInOwnerModeASocialProjectIsAlwaysPaid(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 0.0, projectType: ProjectType::SocialProject);

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, ownerMode: true))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertSame(ProjectContributionFunding::Payment, $option->funding);
    }

    public function testInOwnerModeAProjectIsNotDiscardedForNotCoveringTheWholeFootprint(): void
    {
        $project = $this->adheredProject(purchasedTonnes: 1.0);

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0, ownerMode: true))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertSame(ProjectContributionFunding::OwnCredit, $option->funding);
        $this->assertSame(1.0, $option->ownAvailableTonnes);
    }

    public function testWithoutCompensationThereAreNoOptions(): void
    {
        $this->adheredProject(purchasedTonnes: 10.0);

        $response = $this->createSUT(new AccountFeatures(compensation: false))(new ListContributionOptionsRequest($this->accountId));

        $this->assertTrue($response->isEmpty());
    }

    public function testWithoutCompensationContactIsNotOffered(): void
    {
        $response = $this->createSUT(new AccountFeatures(compensation: false))(new ListContributionOptionsRequest($this->accountId));

        $this->assertFalse($response->compensationEnabled);
        $this->assertFalse($response->offersContact());
    }

    public function testWithCompensationAndNoOptionsContactIsOffered(): void
    {
        $this->adheredProject(purchasedTonnes: 1.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0));

        $this->assertTrue($response->compensationEnabled);
        $this->assertTrue($response->offersContact());
    }

    public function testWithOptionsContactIsNotOffered(): void
    {
        $this->adheredProject(purchasedTonnes: 10.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId));

        $this->assertFalse($response->offersContact());
    }

    public function testOffersTheDistributorProjectToADistributedAccountNotAdheredToIt(): void
    {
        $this->distributeAccount();
        $project = $this->distributorProject(10.0);

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0, allowSharedCredit: true))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertTrue($option->limitedByCredit);
        $this->assertSame(10.0, $option->availableTonnes);
        $this->assertSame(ProjectContributionFunding::Payment, $option->funding);
    }

    public function testWithoutSharedCreditAllowedTheDistributorProjectIsNotOffered(): void
    {
        $this->distributeAccount();
        $this->distributorProject(10.0);
        $ownProject = $this->adheredProject(purchasedTonnes: 10.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0));

        $this->assertCount(1, $response->options);
        $this->assertNotNull($response->find((string) $ownProject->id()));
    }

    public function testTheDistributorProjectIsNotOfferedWithoutCompensationInTheDistributor(): void
    {
        $this->distributor->changeFeatures(new AccountFeatures(compensation: false));
        $this->distributeAccount();
        $this->distributorProject(10.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, allowSharedCredit: true));

        $this->assertTrue($response->isEmpty());
    }

    public function testTheDistributorProjectIsNotOfferedWithoutCompensationInTheDistributedAccount(): void
    {
        $this->distributeAccount();
        $this->distributorProject(10.0);

        $response = $this->createSUT(new AccountFeatures(compensation: false))(new ListContributionOptionsRequest($this->accountId, allowSharedCredit: true));

        $this->assertTrue($response->isEmpty());
    }

    public function testAnAdheredProjectWithoutOwnCreditIsCoveredByTheDistributor(): void
    {
        $this->distributeAccount();
        $project = $this->distributorProject(10.0);
        $this->accountProjects->add(AccountProjectMother::from($this->accountId, $project->id()));

        $option = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, co2eqKg: 2000.0, allowSharedCredit: true))
            ->find((string) $project->id());

        $this->assertNotNull($option);
        $this->assertSame(10.0, $option->availableTonnes);
    }

    public function testInOwnerModeTheDistributorCreditIsNeverOffered(): void
    {
        $this->distributeAccount();
        $sharedProject = $this->distributorProject(10.0);
        $this->accountProjects->add(AccountProjectMother::from($this->accountId, $sharedProject->id()));
        $ownProject = $this->adheredProject(purchasedTonnes: 3.0);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, ownerMode: true, allowSharedCredit: true));

        $this->assertNull($response->find((string) $sharedProject->id()));

        $option = $response->find((string) $ownProject->id());

        $this->assertNotNull($option);
        $this->assertSame(ProjectContributionFunding::OwnCredit, $option->funding);
        $this->assertSame(3.0, $option->availableTonnes);
    }

    public function testAPropertyAssignmentOnlyOffersCreditForThatProperty(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $this->accountId);
        $this->properties[] = $property;
        $this->assignments[] = DistributorAssignmentMother::forProperty($this->distributor->id(), $property->id());
        $project = $this->distributorProject(10.0);

        $sut = $this->createSUT();

        $this->assertTrue($sut(new ListContributionOptionsRequest($this->accountId, allowSharedCredit: true))->isEmpty());
        $this->assertNotNull(
            $sut(new ListContributionOptionsRequest($this->accountId, allowSharedCredit: true, propertyId: $property->id()))
                ->find((string) $project->id()),
        );
    }

    public function testASocialProjectOfTheDistributorIsNotOffered(): void
    {
        $this->distributeAccount();
        $this->distributorProject(10.0, ProjectType::SocialProject);

        $response = $this->createSUT()(new ListContributionOptionsRequest($this->accountId, allowSharedCredit: true));

        $this->assertTrue($response->isEmpty());
    }

    private function distributeAccount(): void
    {
        $this->assignments[] = DistributorAssignmentMother::forAccount($this->distributor->id(), $this->accountId);
    }

    private function distributorProject(float $sharedTonnes, ProjectType $projectType = ProjectType::EmissionCompensation): Project
    {
        $project = ProjectMother::contributable(projectType: $projectType, id: ProjectId::generate());
        $this->projects->add($project);
        $this->accountProjects->add(AccountProjectMother::from($this->distributor->id(), $project->id()));
        $this->creditPurchases->add(CreditPurchaseMother::validated(
            $this->distributor->id(),
            $project->id(),
            tonnes: $sharedTonnes,
            paymentType: CreditPurchasePaymentType::PaidByBioscore,
        ));

        return $project;
    }

    private function adheredProject(
        float $purchasedTonnes,
        ProjectType $projectType = ProjectType::EmissionCompensation,
        float $pricePerTonne = 20.0,
        ?float $costPerTonne = 15.0,
        CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByClient,
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
                CreditPurchaseMother::validated($this->accountId, $projectId, tonnes: $purchasedTonnes, paymentType: $paymentType),
            );
        }

        $project = $this->projects->find($projectId);

        $this->assertNotNull($project);

        return $project;
    }

    private function createSUT(AccountFeatures $features = new AccountFeatures()): ListContributionOptionsService
    {
        $accounts = [AccountMother::withFeatures('account', (string) $this->accountId, $features), $this->distributor];

        return new ListContributionOptionsService(
            $this->accountProjects,
            $this->projects,
            new CreditAvailability($this->creditPurchases, $this->contributions),
            AccountFeaturesPolicyMother::create($accounts),
            CreditSourceSelectorMother::create(
                $this->accountProjects,
                $this->creditPurchases,
                $this->contributions,
                $accounts,
                $this->assignments,
                $this->properties,
            ),
        );
    }
}
