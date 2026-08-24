<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Consent\ConsentProof;

final class AttendeeSurveyResponseConsentGranted extends DomainEvent
{
    protected static string $eventName = 'attendeeSurveyResponseConsentGranted';

    public function __construct(
        private AttendeeSurveyResponseId $attendeeSurveyResponseId,
        private ConsentProof $consentProof,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function attendeeSurveyResponseId(): AttendeeSurveyResponseId
    {
        return $this->attendeeSurveyResponseId;
    }

    public function consentProof(): ConsentProof
    {
        return $this->consentProof;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->attendeeSurveyResponseId();
    }

    public function payload(): array
    {
        return [
            'attendeeSurveyResponseId' => (string) $this->attendeeSurveyResponseId,
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
        return new AttendeeSurveyResponseConsentGranted(
            attendeeSurveyResponseId: new AttendeeSurveyResponseId((string) $payload['attendeeSurveyResponseId']),
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
