<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Consent\ConsentProof;

final class TripCalculationConsentGranted extends DomainEvent
{
    protected static string $eventName = 'tripCalculationConsentGranted';

    public function __construct(
        private TripCalculationId $tripCalculationId,
        private ConsentProof $consentProof,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function tripCalculationId(): TripCalculationId
    {
        return $this->tripCalculationId;
    }

    public function consentProof(): ConsentProof
    {
        return $this->consentProof;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->tripCalculationId();
    }

    public function payload(): array
    {
        return [
            'tripCalculationId' => (string) $this->tripCalculationId,
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
        return new TripCalculationConsentGranted(
            tripCalculationId: new TripCalculationId((string) $payload['tripCalculationId']),
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
