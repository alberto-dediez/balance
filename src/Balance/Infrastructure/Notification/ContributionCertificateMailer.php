<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Notification;

use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateRequest;
use App\Balance\Application\ContributionCertificate\Send\SendContributionCertificateService;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateGenerated;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

/** Sends the finished document to the contributor, in the language they used to contribute. */
class ContributionCertificateMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private SendContributionCertificateService $sendContributionCertificate)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ContributionCertificateGenerated);

        $this->sendContributionCertificate->__invoke(
            new SendContributionCertificateRequest($event->contributionCertificateId()),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ContributionCertificateGenerated;
    }
}
