<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusHumanDonationAssessed extends DomainEvent
{
    protected static string $eventName = 'surplusHumanDonationAssessed';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly bool $canBeHumanDonated,
        private readonly ?ReasonId $reasonId,
        private readonly ?\DateTimeImmutable $donationDate = null,
        private readonly ?string $batch = null,
        private readonly ?RecipientOrganizationId $recipientOrganizationId = null,
        private readonly bool $createDeliveryLog = false,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
    }

    public function canBeHumanDonated(): bool
    {
        return $this->canBeHumanDonated;
    }

    public function reasonId(): ?ReasonId
    {
        return $this->reasonId;
    }

    public function donationDate(): ?\DateTimeImmutable
    {
        return $this->donationDate;
    }

    public function batch(): ?string
    {
        return $this->batch;
    }

    public function recipientOrganizationId(): ?RecipientOrganizationId
    {
        return $this->recipientOrganizationId;
    }

    public function createDeliveryLog(): bool
    {
        return $this->createDeliveryLog;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'canBeHumanDonated' => $this->canBeHumanDonated,
            'reasonId' => null !== $this->reasonId ? (string) $this->reasonId : null,
            'donationDate' => null !== $this->donationDate ? $this->donationDate->format(\DateTimeInterface::ATOM) : null,
            'batch' => $this->batch,
            'recipientOrganizationId' => null !== $this->recipientOrganizationId ? (string) $this->recipientOrganizationId : null,
            'createDeliveryLog' => $this->createDeliveryLog,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusHumanDonationAssessed(
            surplusId: new SurplusId($payload['surplusId']),
            canBeHumanDonated: (bool) $payload['canBeHumanDonated'],
            reasonId: isset($payload['reasonId']) ? new ReasonId((string) $payload['reasonId']) : null,
            donationDate: isset($payload['donationDate']) ? new \DateTimeImmutable($payload['donationDate']) : null,
            batch: $payload['batch'] ?? null,
            recipientOrganizationId: isset($payload['recipientOrganizationId']) ? new RecipientOrganizationId($payload['recipientOrganizationId']) : null,
            createDeliveryLog: (bool) ($payload['createDeliveryLog'] ?? false),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
