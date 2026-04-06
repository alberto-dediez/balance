<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DonationAgreementRemoved extends DomainEvent
{
    protected static string $eventName = 'donationAgreementRemoved';

    public function __construct(
        private DonationAgreementId $donationAgreementId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function donationAgreementId(): DonationAgreementId
    {
        return $this->donationAgreementId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->donationAgreementId();
    }

    public function payload(): array
    {
        return [
            'donationAgreementId' => (string) $this->donationAgreementId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DonationAgreementRemoved(
            donationAgreementId: new DonationAgreementId($payload['donationAgreementId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
