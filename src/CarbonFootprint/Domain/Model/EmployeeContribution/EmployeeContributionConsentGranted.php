<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Consent\ConsentProof;

final class EmployeeContributionConsentGranted extends DomainEvent
{
    protected static string $eventName = 'employeeContributionConsentGranted';

    public function __construct(
        private EmployeeContributionId $employeeContributionId,
        private ConsentProof $consentProof,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeContributionId(): EmployeeContributionId
    {
        return $this->employeeContributionId;
    }

    public function consentProof(): ConsentProof
    {
        return $this->consentProof;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeContributionId();
    }

    public function payload(): array
    {
        return [
            'employeeContributionId' => (string) $this->employeeContributionId,
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
        return new EmployeeContributionConsentGranted(
            employeeContributionId: new EmployeeContributionId((string) $payload['employeeContributionId']),
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
