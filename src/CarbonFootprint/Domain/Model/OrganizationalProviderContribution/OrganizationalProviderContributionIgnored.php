<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalProviderContributionIgnored extends DomainEvent
{
    protected static string $eventName = 'organizationalProviderContributionIgnored';

    public function __construct(
        private OrganizationalProviderContributionId $contributionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OrganizationalProviderContributionId
    {
        return $this->contributionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            contributionId: new OrganizationalProviderContributionId($payload['contributionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
