<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DonationAgreement;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;

interface DonationAgreementUploader
{
    public function upload(string $sourcePath, string $extension, AccountId $accountId, RecipientOrganizationId $recipientOrganizationId): string;
}
