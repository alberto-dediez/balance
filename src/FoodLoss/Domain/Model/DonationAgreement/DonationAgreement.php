<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class DonationAgreement implements \Stringable
{
    use AggregateRoot;
    public const EXPIRING_SOON_DAYS = 30;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private DonationAgreementId $id,
        private ?RecipientOrganizationId $recipientOrganizationId = null,
        private ?AccountId $accountId = null,
        private \DateTimeImmutable $startDate = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $expirationDate = new \DateTimeImmutable('NOW'),
        private ?UserId $uploadedBy = null,
        private string $path = '',
        private bool $removed = false,
        /* @var array<\App\Shared\Domain\Event\DomainEvent> $events */
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
        private ?\DateTimeImmutable $expirationWarningNotifiedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): DonationAgreementId
    {
        return $this->id;
    }

    public function recipientOrganizationId(): ?RecipientOrganizationId
    {
        return $this->recipientOrganizationId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function startDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function expirationDate(): \DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function uploadedBy(): ?UserId
    {
        return $this->uploadedBy;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function expirationWarningNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->expirationWarningNotifiedAt;
    }

    public function markExpirationWarningNotified(): void
    {
        $this->recordApplyAndPublish(
            new DonationAgreementExpirationWarningNotified(
                donationAgreementId: $this->id(),
            ),
        );
    }

    public function register(
        RecipientOrganizationId $recipientOrganizationId,
        AccountId $accountId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $expirationDate,
        UserId $uploadedBy,
        string $path,
    ): void {
        $this->recordApplyAndPublish(
            new DonationAgreementRegistered(
                donationAgreementId: $this->id(),
                recipientOrganizationId: $recipientOrganizationId,
                accountId: $accountId,
                startDate: $startDate,
                expirationDate: $expirationDate,
                uploadedBy: $uploadedBy,
                path: $path,
            ),
        );
    }

    public function update(
        RecipientOrganizationId $recipientOrganizationId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $expirationDate,
        UserId $uploadedBy,
        string $path,
    ): void {
        $this->recordApplyAndPublish(
            new DonationAgreementUpdated(
                donationAgreementId: $this->id(),
                recipientOrganizationId: $recipientOrganizationId,
                startDate: $startDate,
                expirationDate: $expirationDate,
                uploadedBy: $uploadedBy,
                path: $path,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new DonationAgreementRemoved(
                donationAgreementId: $this->id(),
            ),
        );
    }

    public function applyDonationAgreementRegistered(DonationAgreementRegistered $event): void
    {
        $this->recipientOrganizationId = $event->recipientOrganizationId();
        $this->accountId = $event->accountId();
        $this->startDate = $event->startDate();
        $this->expirationDate = $event->expirationDate();
        $this->uploadedBy = $event->uploadedBy();
        $this->path = $event->path();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyDonationAgreementUpdated(DonationAgreementUpdated $event): void
    {
        if ($this->expirationDate->format('Y-m-d') !== $event->expirationDate()->format('Y-m-d')) {
            $this->expirationWarningNotifiedAt = null;
        }

        $this->recipientOrganizationId = $event->recipientOrganizationId();
        $this->startDate = $event->startDate();
        $this->expirationDate = $event->expirationDate();
        $this->uploadedBy = $event->uploadedBy();
        $this->path = $event->path();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyDonationAgreementExpirationWarningNotified(DonationAgreementExpirationWarningNotified $event): void
    {
        $this->expirationWarningNotifiedAt = $event->occurredOn();
    }

    public function applyDonationAgreementRemoved(DonationAgreementRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->recipientOrganizationId;
    }
}
