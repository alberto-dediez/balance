<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\CreditPurchase;

use App\Balance\Application\CreditPurchase\Alerts\CheckCreditAlertsRequest;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditsAllocated;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditsReallocated;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class CheckCreditAlertsOnCreditsAllocatedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private SystemActorResolver $systemActor,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProjectContributionCreditsAllocated || $event instanceof ProjectContributionCreditsReallocated);

        $this->checkAlertsFor($event->projectContributionId());
    }

    private function checkAlertsFor(ProjectContributionId $projectContributionId): void
    {
        $projectContribution = $this->projectContributionRepository->find($projectContributionId);

        if (null === $projectContribution) {
            return;
        }

        $accountId = $projectContribution->accountId();
        $projectId = $projectContribution->projectId();

        if (!$accountId instanceof AccountId || !$projectId instanceof ProjectId) {
            return;
        }

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new CheckCreditAlertsRequest(accountId: $accountId, projectId: $projectId),
                priority: Priority::LOW,
            ),
            ($this->systemActor)(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectContributionCreditsAllocated
            || $event instanceof ProjectContributionCreditsReallocated;
    }
}
