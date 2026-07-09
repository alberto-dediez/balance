<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProviderContributionRegistered extends DomainEvent
{
    protected static string $eventName = 'providerContributionRegistered';

    public function __construct(
        private ProviderContributionId $providerContributionId,
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private AccountId $accountId,
        private ProviderId $providerId,
        #[\SensitiveParameter]
        private string $token,
        private ?string $companyName,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function providerContributionId(): ProviderContributionId
    {
        return $this->providerContributionId;
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->providerContributionId();
    }

    public function payload(): array
    {
        return [
            'providerContributionId' => (string) $this->providerContributionId,
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'accountId' => (string) $this->accountId,
            'providerId' => (string) $this->providerId,
            'token' => $this->token,
            'companyName' => $this->companyName,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            providerContributionId: new ProviderContributionId($payload['providerContributionId']),
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            accountId: new AccountId($payload['accountId']),
            providerId: new ProviderId($payload['providerId']),
            token: $payload['token'],
            companyName: $payload['companyName'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
