<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ContributionCertificateGenerated extends DomainEvent
{
    protected static string $eventName = 'contributionCertificateGenerated';

    /** @param array<string, string> $files Stored path per locale. */
    public function __construct(
        private ContributionCertificateId $contributionCertificateId,
        private array $files,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionCertificateId(): ContributionCertificateId
    {
        return $this->contributionCertificateId;
    }

    /** @return array<string, string> */
    public function files(): array
    {
        return $this->files;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionCertificateId();
    }

    public function payload(): array
    {
        return [
            'contributionCertificateId' => (string) $this->contributionCertificateId,
            'files' => $this->files,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        /** @var array<string, string> $files */
        $files = $payload['files'] ?? [];

        return new ContributionCertificateGenerated(
            contributionCertificateId: new ContributionCertificateId($payload['contributionCertificateId']),
            files: $files,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
