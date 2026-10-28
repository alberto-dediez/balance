<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalProviderContributionGeneralInfoUpdated extends DomainEvent
{
    protected static string $eventName = 'organizationalProviderContributionGeneralInfoUpdated';

    public function __construct(
        private OrganizationalProviderContributionId $contributionId,
        private ?string $companyName,
        private ?string $service,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OrganizationalProviderContributionId
    {
        return $this->contributionId;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function service(): ?string
    {
        return $this->service;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'companyName' => $this->companyName,
            'service' => $this->service,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            contributionId: new OrganizationalProviderContributionId($payload['contributionId']),
            companyName: $payload['companyName'] ?? null,
            service: $payload['service'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
