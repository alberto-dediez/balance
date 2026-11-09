<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Assign;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Application\CreditPurchase\Assign\AssignCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Assign\AssignCreditPurchaseService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Validate\ValidateCreditPurchaseService;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CompensationNotEnabledForAccountException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseAssigned;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseFilter;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseOrigin;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRequested;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseValidated;
use App\Balance\Domain\Model\CreditPurchase\DuplicatedPurchaseReferenceException;
use App\Balance\Domain\Model\CreditPurchase\ProjectNotCompensableException;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AssignCreditPurchaseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private CreditPurchaseRepository $creditPurchaseRepository;

    private ProjectRepository $projectRepository;

    private AccountProjectRepository $accountProjectRepository;

    private AccountId $accountId;

    private Project $project;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $this->projectRepository = ProjectRepositoryMother::inMemory();
        $this->accountProjectRepository = AccountProjectRepositoryMother::inMemory();
        $this->accountId = AccountId::generate();
        $this->project = ProjectMother::random();
        $this->projectRepository->add($this->project);
    }

    public function testAdminAssignsCreditsAndSelectsProject(): void
    {
        $result = $this->createSUT()($this->request(tonnes: 25.0, reference: 'ASSIGN-1'), UserMother::adminUser());

        $creditPurchase = $this->creditPurchaseRepository->find($result->creditPurchaseId);

        $this->assertNotNull($creditPurchase);
        $this->assertSame(CreditPurchaseStatus::Validated, $creditPurchase->status());
        $this->assertSame(CreditPurchaseOrigin::Assigned, $creditPurchase->origin());
        $this->assertSame(25.0, $creditPurchase->availableTonnes());
        $this->assertSame('ASSIGN-1', $creditPurchase->reference());
        $this->assertFalse($result->validatedPendingRequest);
        $this->assertFalse($result->partiallyFulfilled);
        $this->assertSame(0, $result->remainingPendingRequests);

        $selected = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $this->accountId, projectId: $this->project->id(), limit: 0),
        );

        $this->assertCount(1, $selected);
        $this->assertInstanceOf(CreditPurchaseAssigned::class, $this->spySubscriber->lastEvent());
    }

    public function testSuperAdminCanAssign(): void
    {
        $result = $this->createSUT()($this->request(), UserMother::superAdminUser());

        $this->assertNotNull($this->creditPurchaseRepository->find($result->creditPurchaseId));
    }

    public function testOwnerIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->accountId);

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()($this->request(), $user);
    }

    public function testDoesNotDuplicateSelectionWhenAlreadyAdhered(): void
    {
        $sut = $this->createSUT();

        $sut($this->request(reference: 'ASSIGN-1'), UserMother::adminUser());
        $sut($this->request(reference: 'ASSIGN-2'), UserMother::adminUser());

        $selected = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $this->accountId, projectId: $this->project->id(), limit: 0),
        );

        $this->assertCount(1, $selected);
    }

    public function testValidatesTheOldestPendingRequestInsteadOfCreatingANewPurchase(): void
    {
        $newest = $this->pendingRequestedAt(new \DateTimeImmutable('-1 day'), 10.0);
        $oldest = $this->pendingRequestedAt(new \DateTimeImmutable('-10 days'), 10.0);
        $this->creditPurchaseRepository->add($newest);
        $this->creditPurchaseRepository->add($oldest);

        $result = $this->createSUT()(
            $this->request(tonnes: 25.0, reference: 'ASSIGN-3', paymentType: CreditPurchasePaymentType::PaidByClient, amount: 300.0),
            UserMother::adminUser(),
        );

        $this->assertTrue($oldest->id()->equals($result->creditPurchaseId));
        $this->assertTrue($result->validatedPendingRequest);
        $this->assertFalse($result->partiallyFulfilled);
        $this->assertSame(1, $result->remainingPendingRequests);

        $this->assertSame(CreditPurchaseStatus::Validated, $oldest->status());
        $this->assertSame(25.0, $oldest->purchasedTonnes());
        $this->assertSame(10.0, $oldest->requestedTonnes());
        $this->assertSame('ASSIGN-3', $oldest->reference());
        $this->assertSame(300.0, $oldest->amount());
        $this->assertSame(CreditPurchasePaymentType::PaidByClient, $oldest->paymentType());
        $this->assertSame(CreditPurchaseOrigin::Requested, $oldest->origin());
        $this->assertSame(CreditPurchaseStatus::Pending, $newest->status());

        $this->assertCount(2, $this->creditPurchaseRepository->findByFilter(
            new CreditPurchaseFilter(accountId: $this->accountId, limit: 0),
        ));
        $this->assertInstanceOf(CreditPurchaseValidated::class, $this->spySubscriber->lastEvent());
    }

    public function testValidatingAPendingRequestWithFewerTonnesIsPartial(): void
    {
        $pending = CreditPurchaseMother::pending($this->accountId, $this->project->id(), 10.0);
        $this->creditPurchaseRepository->add($pending);

        $result = $this->createSUT()($this->request(tonnes: 6.0), UserMother::adminUser());

        $this->assertTrue($result->validatedPendingRequest);
        $this->assertTrue($result->partiallyFulfilled);
        $this->assertSame(0, $result->remainingPendingRequests);
        $this->assertTrue($pending->isPartiallyFulfilled());
    }

    public function testPendingRequestOfAnotherProjectIsIgnored(): void
    {
        $pending = CreditPurchaseMother::pending($this->accountId, ProjectId::generate(), 10.0);
        $this->creditPurchaseRepository->add($pending);

        $result = $this->createSUT()($this->request(), UserMother::adminUser());

        $this->assertFalse($result->validatedPendingRequest);
        $this->assertFalse($pending->id()->equals($result->creditPurchaseId));
        $this->assertSame(CreditPurchaseStatus::Pending, $pending->status());
        $this->assertInstanceOf(CreditPurchaseAssigned::class, $this->spySubscriber->lastEvent());
    }

    public function testThrowsWhenAccountHasNotCompensationEnabled(): void
    {
        $sut = $this->createSUT(new AccountFeatures(compensation: false));

        try {
            $sut($this->request(), UserMother::adminUser());
            $this->fail('Expected CompensationNotEnabledForAccountException');
        } catch (CompensationNotEnabledForAccountException $exception) {
            $this->assertTrue($this->accountId->equals($exception->accountId()));
        }

        $this->assertCount(0, $this->creditPurchaseRepository->findByFilter(new CreditPurchaseFilter(limit: 0)));
    }

    public function testThrowsWhenProjectIsNotCompensable(): void
    {
        $project = ProjectMother::from(
            name: 'social-project',
            id: (string) ProjectId::generate(),
            projectType: ProjectType::SocialProject,
            projectStatus: ProjectStatus::Pending,
        );
        $this->projectRepository->add($project);

        $this->expectException(ProjectNotCompensableException::class);

        $this->createSUT()($this->request(projectId: $project->id()), UserMother::adminUser());
    }

    public function testThrowsWhenProjectDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()($this->request(projectId: ProjectId::generate()), UserMother::adminUser());
    }

    public function testThrowsWhenReferenceIsDuplicated(): void
    {
        $this->creditPurchaseRepository->add(CreditPurchaseMother::assigned(reference: 'REF-1'));

        try {
            $this->createSUT()($this->request(reference: 'REF-1'), UserMother::adminUser());
            $this->fail('Expected DuplicatedPurchaseReferenceException');
        } catch (DuplicatedPurchaseReferenceException) {
        }

        $this->assertCount(0, $this->creditPurchaseRepository->findByFilter(
            new CreditPurchaseFilter(accountId: $this->accountId, limit: 0),
        ));
    }

    public function testThrowsWhenReferenceIsDuplicatedValidatingAPendingRequest(): void
    {
        $this->creditPurchaseRepository->add(CreditPurchaseMother::assigned(reference: 'REF-1'));
        $pending = CreditPurchaseMother::pending($this->accountId, $this->project->id(), 10.0);
        $this->creditPurchaseRepository->add($pending);

        try {
            $this->createSUT()($this->request(reference: 'REF-1'), UserMother::adminUser());
            $this->fail('Expected DuplicatedPurchaseReferenceException');
        } catch (DuplicatedPurchaseReferenceException) {
        }

        $this->assertSame(CreditPurchaseStatus::Pending, $pending->status());
    }

    public function testThrowsWhenAccountOrProjectIsMissing(): void
    {
        $sut = $this->createSUT();

        try {
            $sut(new AssignCreditPurchaseRequest(projectId: $this->project->id(), purchasedTonnes: 10.0, reference: 'R'), UserMother::adminUser());
            $this->fail('Expected InvalidArgumentException for the account');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);

        $sut(new AssignCreditPurchaseRequest(accountId: $this->accountId, purchasedTonnes: 10.0, reference: 'R'), UserMother::adminUser());
    }

    public function testDoesNotPublishRequestedNorValidatedWhenCreatingANewPurchase(): void
    {
        $this->createSUT()($this->request(), UserMother::adminUser());

        foreach ($this->spySubscriber->events() as $event) {
            $this->assertNotInstanceOf(CreditPurchaseRequested::class, $event);
            $this->assertNotInstanceOf(CreditPurchaseValidated::class, $event);
        }

        $this->assertInstanceOf(CreditPurchaseAssigned::class, $this->spySubscriber->lastEvent());
    }

    private function request(
        float $tonnes = 10.0,
        string $reference = 'ASSIGN-REF',
        CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByBioscore,
        ?float $amount = null,
        ?ProjectId $projectId = null,
    ): AssignCreditPurchaseRequest {
        return new AssignCreditPurchaseRequest(
            accountId: $this->accountId,
            projectId: $projectId ?? $this->project->id(),
            purchasedTonnes: $tonnes,
            reference: $reference,
            paymentType: $paymentType,
            amount: $amount,
        );
    }

    private function pendingRequestedAt(\DateTimeImmutable $requestedAt, float $tonnes): CreditPurchase
    {
        return new CreditPurchase(
            id: CreditPurchaseId::generate(),
            accountId: $this->accountId,
            projectId: $this->project->id(),
            requestedBy: UserId::generate(),
            requestedTonnes: $tonnes,
            requestedAt: $requestedAt,
        );
    }

    private function createSUT(AccountFeatures $features = new AccountFeatures()): AssignCreditPurchaseService
    {
        $selectProjectService = new SelectProjectService(
            accountProjectRepository: $this->accountProjectRepository,
            projectRepository: $this->projectRepository,
            authorizer: new AccountProjectAuthorizer(),
        );

        return new AssignCreditPurchaseService(
            creditPurchaseRepository: $this->creditPurchaseRepository,
            projectRepository: $this->projectRepository,
            accountProjectRepository: $this->accountProjectRepository,
            selectProjectService: $selectProjectService,
            validateCreditPurchase: new ValidateCreditPurchaseService(
                creditPurchaseRepository: $this->creditPurchaseRepository,
                authorizer: new CreditPurchaseAuthorizer(),
            ),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount($this->accountId, $features),
            authorizer: new CreditPurchaseAuthorizer(),
        );
    }
}
