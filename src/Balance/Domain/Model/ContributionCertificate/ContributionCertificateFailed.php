<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContributionCertificateFailed extends DomainEvent
{
    protected static string $eventName = 'contributionCertificateFailed';

    public function __construct(
        private ContributionCertificateId $contributionCertificateId,
        private string $reason,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionCertificateId(): ContributionCertificateId
    {
        return $this->contributionCertificateId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionCertificateId();
    }

    public function payload(): array
    {
        return [
            'contributionCertificateId' => (string) $this->contributionCertificateId,
            'reason' => $this->reason,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ContributionCertificateFailed(
            contributionCertificateId: new ContributionCertificateId($payload['contributionCertificateId']),
            reason: (string) $payload['reason'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
