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

/**
 * Drawing the document takes seconds, so it is deferred: neither the Stripe webhook nor the browser
 * coming back from the gateway waits for it.
 */
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
                // No language is passed: the certificate takes the one the contribution was
                // registered with, which is not necessarily the one this confirmation runs in
                // (the Stripe webhook and the expiration command carry no language of their own).
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
