<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProviderContributionBreakdownAnswered extends DomainEvent
{
    protected static string $eventName = 'providerContributionBreakdownAnswered';

    public function __construct(
        private ProviderContributionId $providerContributionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function providerContributionId(): ProviderContributionId
    {
        return $this->providerContributionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->providerContributionId();
    }

    public function payload(): array
    {
        return [
            'providerContributionId' => (string) $this->providerContributionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            providerContributionId: new ProviderContributionId($payload['providerContributionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
