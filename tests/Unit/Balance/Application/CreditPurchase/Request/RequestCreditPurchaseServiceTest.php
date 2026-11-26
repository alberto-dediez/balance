<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Request;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Request\CreditPurchaseQuoter;
use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseService;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseQuoteChangedException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRequested;
use App\Balance\Domain\Model\CreditPurchase\ProjectNotCompensableException;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Payments\Domain\Model\Vat\VatCalculator;
use App\Payments\Domain\Model\Vat\VatCountry;
use App\Payments\Domain\Model\Vat\VatExemptionReason;
use App\Payments\Domain\Model\Vat\VatNumberFormatValidator;
use App\Payments\Infrastructure\Vat\InMemory\InMemoryVatCountryProvider;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RequestCreditPurchaseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvokeRequestsCreditsAndSelectsProject(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::contributable();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $result = $sut($this->request($accountId, $project->id(), 10.0, 181.5), $user);

        $creditPurchase = $creditPurchaseRepository->find($result->creditPurchaseId);

        $this->assertNotNull($creditPurchase);
        $this->assertSame(10.0, $creditPurchase->requestedTonnes());

        $selected = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $project->id(), limit: 1),
        );

        $this->assertCount(1, $selected);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRequested::class, $lastEvent);
    }

    public function testInvokeDoesNotDuplicateSelectionWhenAlreadyAdhered(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::contributable();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $sut($this->request($accountId, $project->id(), 5.0, 90.75), $user);
        $sut($this->request($accountId, $project->id(), 3.0, 54.45), $user);

        $selected = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $project->id(), limit: 0),
        );

        $this->assertCount(1, $selected);
    }

    public function testThrowsWhenProjectIsNotCompensable(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::from(
            name: 'social-project',
            id: (string) ProjectId::generate(),
            projectType: ProjectType::SocialProject,
            projectStatus: ProjectStatus::Pending,
        );
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $this->expectException(ProjectNotCompensableException::class);

        $sut($this->request($accountId, $project->id(), 10.0, 181.5), $user);
    }

    public function testThrowsWhenProjectDoesNotExist(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $this->expectException(\InvalidArgumentException::class);

        $sut($this->request($accountId, ProjectMother::contributable()->id(), 10.0, 181.5), $user);
    }

    public function testThrowsWhenUserDoesNotBelongToAccount(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::contributable();
        $projectRepository->add($project);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $this->expectException(UnauthorizedActionException::class);

        $sut($this->request(AccountId::generate(), $project->id(), 10.0, 181.5), $user);
    }

    public function testInvokeStoresTheQuoteAndTheBillingAndTheEventCarriesThem(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();

        $project = ProjectMother::contributable();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, AccountProjectRepositoryMother::inMemory());

        $result = $sut($this->request($accountId, $project->id(), 10.0, 181.5), $user);

        $creditPurchase = $creditPurchaseRepository->find($result->creditPurchaseId);

        $this->assertNotNull($creditPurchase);
        $this->assertSame(150.0, $creditPurchase->requestedQuote()?->taxableAmount());
        $this->assertSame(31.5, $creditPurchase->requestedQuote()->vatAmount());
        $this->assertSame(VatExemptionReason::Standard, $creditPurchase->requestedQuote()->vatReason());
        $this->assertSame('Mi Empresa SL', $creditPurchase->billing()?->legalName());
        $this->assertSame('ESP', $creditPurchase->billing()->country());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRequested::class, $lastEvent);
        $this->assertSame(181.5, $lastEvent->quote()?->totalAmount());
        $this->assertSame('facturas@example.com', $lastEvent->billing()?->email());
    }

    public function testThrowsWhenTheTermsAreNotAcceptedAndDoesNotRequestNorSelect(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::contributable();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $request = $this->request($accountId, $project->id(), 10.0, 181.5);
        $request->termsAccepted = false;

        try {
            $sut($request, $user);
            $this->fail('An exception was expected.');
        } catch (\InvalidArgumentException) {
        }

        $this->assertSame(0, $creditPurchaseRepository->totalResultsByFilter(new CreditPurchaseFilter(accountId: $accountId, limit: 0)));
        $this->assertCount(0, $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $project->id(), limit: 0),
        ));
    }

    public function testThrowsWhenTheAcceptedTotalDiffers(): void
    {
        $this->expectQuoteChanged(ProjectMother::contributable(), 150.0);
    }

    public function testThrowsWhenTheAcceptedTotalIsMissingWithCostPerTonne(): void
    {
        $this->expectQuoteChanged(ProjectMother::contributable(), null);
    }

    public function testThrowsWhenTheAcceptedTotalIsGivenWithoutCostPerTonne(): void
    {
        $this->expectQuoteChanged(ProjectMother::contributable(costPerTonne: null), 181.5);
    }

    public function testInvokeRequestsWithoutQuoteWhenTheProjectHasNoCostPerTonne(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();

        $project = ProjectMother::contributable(costPerTonne: null);
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, AccountProjectRepositoryMother::inMemory());

        $result = $sut($this->request($accountId, $project->id(), 10.0, null), $user);

        $creditPurchase = $creditPurchaseRepository->find($result->creditPurchaseId);

        $this->assertNotNull($creditPurchase);
        $this->assertNull($creditPurchase->requestedQuote());
        $this->assertNotNull($creditPurchase->billing());
    }

    public function testThrowsWithAnIncompleteBilling(): void
    {
        $projectRepository = ProjectRepositoryMother::inMemory();

        $project = ProjectMother::contributable();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT(CreditPurchaseRepositoryMother::inMemory(), $projectRepository, AccountProjectRepositoryMother::inMemory());

        $request = $this->request($accountId, $project->id(), 10.0, 181.5);
        $request->city = '';

        $this->expectException(\InvalidArgumentException::class);

        $sut($request, $user);
    }

    private function expectQuoteChanged(Project $project, ?float $acceptedTotal): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, AccountProjectRepositoryMother::inMemory());

        try {
            $sut($this->request($accountId, $project->id(), 10.0, $acceptedTotal), $user);
            $this->fail('An exception was expected.');
        } catch (CreditPurchaseQuoteChangedException) {
        }

        $this->assertSame(0, $creditPurchaseRepository->totalResultsByFilter(new CreditPurchaseFilter(accountId: $accountId, limit: 0)));
    }

    private function request(AccountId $accountId, ProjectId $projectId, float $tonnes, ?float $acceptedTotal): RequestCreditPurchaseRequest
    {
        return new RequestCreditPurchaseRequest(
            accountId: $accountId,
            projectId: $projectId,
            tonnes: $tonnes,
            legalName: 'Mi Empresa SL',
            taxId: 'B12345678',
            street: 'Calle Mayor 1',
            postalCode: '28001',
            city: 'Madrid',
            country: 'ESP',
            email: 'facturas@example.com',
            termsAccepted: true,
            acceptedTotal: $acceptedTotal,
        );
    }

    private function createSUT(
        CreditPurchaseRepository $creditPurchaseRepository,
        ProjectRepository $projectRepository,
        AccountProjectRepository $accountProjectRepository,
    ): RequestCreditPurchaseService {
        $selectProjectService = new SelectProjectService(
            accountProjectRepository: $accountProjectRepository,
            projectRepository: $projectRepository,
            authorizer: new AccountProjectAuthorizer(),
        );

        return new RequestCreditPurchaseService(
            creditPurchaseRepository: $creditPurchaseRepository,
            projectRepository: $projectRepository,
            accountProjectRepository: $accountProjectRepository,
            selectProjectService: $selectProjectService,
            authorizer: new CreditPurchaseAuthorizer(),
            quoter: new CreditPurchaseQuoter(
                new VatCalculator(
                    new VatNumberFormatValidator(),
                    new InMemoryVatCountryProvider([
                        new VatCountry('ESP', 21.0, true),
                        new VatCountry('FRA', 20.0, true),
                    ]),
                    homeCountry: 'ESP',
                ),
            ),
        );
    }
}
