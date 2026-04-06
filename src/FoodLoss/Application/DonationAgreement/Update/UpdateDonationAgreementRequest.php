<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\Update;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class UpdateDonationAgreementRequest implements Command
{
    public function __construct(
        public DonationAgreementId $donationAgreementId,
        public ?RecipientOrganizationId $recipientOrganizationId = null,
        public ?\DateTimeImmutable $startDate = null,
        public ?\DateTimeImmutable $expirationDate = null,
        public ?UserId $uploadedBy = null,
        public ?string $temporaryFilePath = null,
        public ?string $fileExtension = null,
    ) {}
}
