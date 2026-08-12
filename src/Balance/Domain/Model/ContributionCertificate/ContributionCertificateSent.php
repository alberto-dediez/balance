<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContributionCertificateSent extends DomainEvent
{
    protected static string $eventName = 'contributionCertificateSent';

    public function __construct(
        private ContributionCertificateId $contributionCertificateId,
        private string $email,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionCertificateId(): ContributionCertificateId
    {
        return $this->contributionCertificateId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionCertificateId();
    }

    public function payload(): array
    {
        return [
            'contributionCertificateId' => (string) $this->contributionCertificateId,
            'email' => $this->email,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ContributionCertificateSent(
            contributionCertificateId: new ContributionCertificateId($payload['contributionCertificateId']),
            email: (string) $payload['email'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
