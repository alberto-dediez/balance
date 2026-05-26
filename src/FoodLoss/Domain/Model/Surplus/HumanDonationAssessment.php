<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;

class HumanDonationAssessment
{
    public function __construct(
        private readonly ?bool $canBeHumanDonated = null,
        private readonly ?ReasonId $reasonId = null,
        private readonly ?\DateTimeImmutable $donationDate = null,
        private readonly ?string $batch = null,
        private readonly ?RecipientOrganizationId $recipientOrganizationId = null,
        private readonly bool $createDeliveryLog = false,
    ) {}

    public function canBeHumanDonated(): bool
    {
        return true === $this->canBeHumanDonated;
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

    public function isAnswered(): bool
    {
        return null !== $this->canBeHumanDonated;
    }
}
