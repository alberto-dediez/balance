<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Consent\ConsentProof;

final class OrganizationalProviderContributionConsentGranted extends DomainEvent
{
    protected static string $eventName = 'organizationalProviderContributionConsentGranted';

    public function __construct(
        private OrganizationalProviderContributionId $contributionId,
        private ConsentProof $consentProof,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OrganizationalProviderContributionId
    {
        return $this->contributionId;
    }

    public function consentProof(): ConsentProof
    {
        return $this->consentProof;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
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
        return new self(
            contributionId: new OrganizationalProviderContributionId((string) $payload['contributionId']),
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
