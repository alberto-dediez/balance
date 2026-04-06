<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement\Register;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class RegisterDonationAgreementRequest implements Command
{
    public function __construct(
        public ?RecipientOrganizationId $recipientOrganizationId = null,
        public ?AccountId $accountId = null,
        public ?\DateTimeImmutable $startDate = null,
        public ?\DateTimeImmutable $expirationDate = null,
        public ?UserId $uploadedBy = null,
        public string $temporaryFilePath = '',
        public string $fileExtension = '',
    ) {}
}
