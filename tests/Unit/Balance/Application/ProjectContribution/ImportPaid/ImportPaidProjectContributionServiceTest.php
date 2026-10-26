<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\ImportPaid;

use App\Balance\Application\ProjectContribution\ImportPaid\ImportPaidProjectContributionRequest;
use App\Balance\Application\ProjectContribution\ImportPaid\ImportPaidProjectContributionService;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\DuplicatedProjectContributionException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFunding;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionImportedAsPaid;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaid;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ImportPaidProjectContributionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryProjectContributionRepository $projectContributionRepository;

    private InMemoryProjectRepository $projectRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->projectContributionRepository = new InMemoryProjectContributionRepository();
        $this->projectRepository = new InMemoryProjectRepository();
    }

    public function testImportsAPaidContributionWithTheTonnesOfTheProjectPrice(): void
    {
        $project = ProjectMother::contributable(pricePerTonne: 30.0);
        $this->projectRepository->add($project);
        $paidAt = new \DateTimeImmutable('2025-05-20 12:30:00');

        $response = ($this->createSUT())($this->request($project->id(), amount: 10.0, paidAt: $paidAt), UserMother::systemUser());

        $projectContribution = $this->projectContributionRepository->find($response->projectContributionId);

        $this->assertNotNull($projectContribution);
        $this->assertSame(0.333333, $response->tonnes);
        $this->assertSame(0.333333, $projectContribution->tonnes());
        $this->assertTrue($projectContribution->isPaid());
        $this->assertEquals($paidAt, $projectContribution->paidAt());
        $this->assertEquals($paidAt, $projectContribution->resolvedAt());
        $this->assertEquals($paidAt, $projectContribution->registeredAt());
        $this->assertSame(ProjectContributionOrigin::TripCalculation, $projectContribution->origin());
        $this->assertNull($projectContribution->originId());
        $this->assertTrue($projectContribution->isFreeAmount());
        $this->assertSame(10.0, $projectContribution->contributionAmount());
        $this->assertSame(10.0, $projectContribution->totalAmount());
        $this->assertNull($projectContribution->paymentId());
        $this->assertSame(ProjectContributionFunding::Imported, $projectContribution->funding());
        $this->assertSame('Ana Donante', $projectContribution->contributorName());
        $this->assertSame('ana@example.com', $projectContribution->contributorEmail());
        $this->assertInstanceOf(ProjectContributionImportedAsPaid::class, $this->spySubscriber->lastEvent());
        $this->assertSame([], array_filter(
            $this->spySubscriber->events(),
            static fn(DomainEvent $event): bool => $event instanceof ProjectContributionPaid,
        ));
    }

    public function testThrowsWhenTheContributionIsAlreadyImported(): void
    {
        $project = ProjectMother::contributable();
        $this->projectRepository->add($project);
        $request = $this->request($project->id());

        ($this->createSUT())($request, UserMother::systemUser());

        $this->expectException(DuplicatedProjectContributionException::class);

        ($this->createSUT())($request, UserMother::systemUser());
    }

    public function testThrowsWhenTheProjectHasNoPricePerTonne(): void
    {
        $project = ProjectMother::random();
        $this->projectRepository->add($project);

        $this->expectException(\InvalidArgumentException::class);

        ($this->createSUT())($this->request($project->id()), UserMother::systemUser());
    }

    public function testThrowsWhenTheProjectDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->createSUT())($this->request(ProjectId::generate()), UserMother::systemUser());
    }

    public function testDeniesAUserWithoutPermission(): void
    {
        $project = ProjectMother::contributable();
        $this->projectRepository->add($project);

        $this->expectException(UnauthorizedActionException::class);

        ($this->createSUT())(
            $this->request($project->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        );
    }

    private function request(ProjectId $projectId, float $amount = 20.0, ?\DateTimeImmutable $paidAt = null): ImportPaidProjectContributionRequest
    {
        return new ImportPaidProjectContributionRequest(
            projectContributionId: ProjectContributionId::generate(),
            accountId: new AccountId('account-id'),
            projectId: $projectId,
            amount: $amount,
            contributorName: 'Ana Donante',
            contributorEmail: 'ana@example.com',
            paidAt: $paidAt ?? new \DateTimeImmutable('2025-01-01 10:00:00'),
        );
    }

    private function createSUT(): ImportPaidProjectContributionService
    {
        return new ImportPaidProjectContributionService(
            $this->projectContributionRepository,
            $this->projectRepository,
            new ProjectContributionAuthorizer(),
        );
    }
}
