<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DonationAgreementUpdated extends DomainEvent
{
    protected static string $eventName = 'donationAgreementUpdated';

    public function __construct(
        private DonationAgreementId $donationAgreementId,
        private RecipientOrganizationId $recipientOrganizationId,
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $expirationDate,
        private UserId $uploadedBy,
        private string $path,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function donationAgreementId(): DonationAgreementId
    {
        return $this->donationAgreementId;
    }

    public function recipientOrganizationId(): RecipientOrganizationId
    {
        return $this->recipientOrganizationId;
    }

    public function startDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function expirationDate(): \DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function uploadedBy(): UserId
    {
        return $this->uploadedBy;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->donationAgreementId();
    }

    public function payload(): array
    {
        return [
            'donationAgreementId' => (string) $this->donationAgreementId,
            'recipientOrganizationId' => (string) $this->recipientOrganizationId,
            'startDate' => $this->startDate->format(\DateTimeInterface::ATOM),
            'expirationDate' => $this->expirationDate->format(\DateTimeInterface::ATOM),
            'uploadedBy' => (string) $this->uploadedBy,
            'path' => $this->path,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DonationAgreementUpdated(
            donationAgreementId: new DonationAgreementId($payload['donationAgreementId']),
            recipientOrganizationId: new RecipientOrganizationId($payload['recipientOrganizationId']),
            startDate: new \DateTimeImmutable($payload['startDate']),
            expirationDate: new \DateTimeImmutable($payload['expirationDate']),
            uploadedBy: new UserId($payload['uploadedBy']),
            path: $payload['path'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
