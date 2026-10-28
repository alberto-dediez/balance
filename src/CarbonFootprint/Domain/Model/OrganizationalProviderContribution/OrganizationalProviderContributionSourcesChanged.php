<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalProviderContributionSourcesChanged extends DomainEvent
{
    protected static string $eventName = 'organizationalProviderContributionSourcesChanged';

    /** @param list<Source> $sources */
    public function __construct(
        private OrganizationalProviderContributionId $contributionId,
        private array $sources,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OrganizationalProviderContributionId
    {
        return $this->contributionId;
    }

    /** @return list<Source> */
    public function sources(): array
    {
        return $this->sources;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'sources' => array_map(static fn(Source $source): string => $source->value, $this->sources),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            contributionId: new OrganizationalProviderContributionId($payload['contributionId']),
            sources: array_values(array_map(static fn(string $source): Source => Source::from($source), $payload['sources'] ?? [])),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
