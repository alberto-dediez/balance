<?php

declare(strict_types=1);

namespace App\Balance\Domain\Event\ProjectContribution;

use App\Balance\Application\ContributionCertificate\Issue\IssueContributionCertificateRequest;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaid;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class IssueContributionCertificateOnPaidSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private SystemActorResolver $systemActor)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProjectContributionPaid);

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new IssueContributionCertificateRequest($event->projectContributionId()),
                priority: Priority::LOW,
            ),
            ($this->systemActor)(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectContributionPaid;
    }
}
