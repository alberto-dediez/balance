<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Consent\ConsentProof;

final class ProviderContributionConsentGranted extends DomainEvent
{
    protected static string $eventName = 'providerContributionConsentGranted';

    public function __construct(
        private ProviderContributionId $providerContributionId,
        private ConsentProof $consentProof,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function providerContributionId(): ProviderContributionId
    {
        return $this->providerContributionId;
    }

    public function consentProof(): ConsentProof
    {
        return $this->consentProof;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->providerContributionId();
    }

    public function payload(): array
    {
        return [
            'providerContributionId' => (string) $this->providerContributionId,
            'consentedAt' => $this->consentProof->consentedAt()?->format(\DateTimeInterface::ATOM),
            'policyVersion' => $this->consentProof->policyVersion(),
            'locale' => $this->consentProof->locale(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProviderContributionConsentGranted(
            providerContributionId: new ProviderContributionId((string) $payload['providerContributionId']),
            consentProof: new ConsentProof(
                consentedAt: new \DateTimeImmutable((string) $payload['consentedAt']),
                policyVersion: (string) $payload['policyVersion'],
                locale: (string) $payload['locale'],
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
