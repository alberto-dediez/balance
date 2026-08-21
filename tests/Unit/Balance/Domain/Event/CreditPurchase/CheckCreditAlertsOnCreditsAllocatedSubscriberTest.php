<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Event\CreditPurchase;

use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsRequest;
use App\Balance\Domain\Event\CreditPurchase\CheckCreditAlertsOnCreditsAllocatedSubscriber;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditsAllocated;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionExpired;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class CheckCreditAlertsOnCreditsAllocatedSubscriberTest extends TestCase
{
    private SluttyCommandHandler $commandHandler;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->commandHandler = new SluttyCommandHandler();
    }

    public function testIsSubscribedToCreditAllocationEvents(): void
    {
        $sut = $this->createSUT(ProjectContributionRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new ProjectContributionCreditsAllocated(
            projectContributionId: ProjectContributionId::generate(),
            lines: [],
        )));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(ProjectContributionRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new ProjectContributionExpired(
            projectContributionId: ProjectContributionId::generate(),
        )));
    }

    public function testHandleDefersTheCheckOfTheContributionAccountAndProject(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $repository = ProjectContributionRepositoryMother::inMemory();
        $projectContribution = ProjectContributionMother::paid($accountId, $projectId, 9.5);
        $repository->add($projectContribution);

        $sut = $this->createSUT($repository);
        $sut->handle(new ProjectContributionCreditsAllocated(
            projectContributionId: $projectContribution->id(),
            lines: [],
        ));

        $commands = $this->commandHandler->commands();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);

        $request = $commands[0]['command']->command();

        $this->assertInstanceOf(CheckCreditAlertsRequest::class, $request);
        $this->assertSame((string) $accountId, (string) $request->accountId);
        $this->assertSame((string) $projectId, (string) $request->projectId);
    }

    public function testHandleIgnoresUnknownContributions(): void
    {
        $sut = $this->createSUT(ProjectContributionRepositoryMother::inMemory());
        $sut->handle(new ProjectContributionCreditsAllocated(
            projectContributionId: ProjectContributionId::generate(),
            lines: [new AllocatedCreditLine(0, CreditPurchaseId::generate(), null, 1.0, 0.0)],
        ));

        $this->assertSame([], $this->commandHandler->commands());
    }

    private function createSUT(ProjectContributionRepository $projectContributionRepository): CheckCreditAlertsOnCreditsAllocatedSubscriber
    {
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRole('admin', Role::Admin));

        return new CheckCreditAlertsOnCreditsAllocatedSubscriber(
            $projectContributionRepository,
            new SystemActorResolver($userRepository),
        );
    }
}
