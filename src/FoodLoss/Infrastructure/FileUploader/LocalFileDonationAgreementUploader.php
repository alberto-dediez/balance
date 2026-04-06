<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\FileUploader;

use App\FoodLoss\Application\DonationAgreement\DonationAgreementUploader;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;

class LocalFileDonationAgreementUploader implements DonationAgreementUploader
{
    public function __construct(private string $uploadsDir) {}

    public function upload(string $sourcePath, string $extension, AccountId $accountId, RecipientOrganizationId $recipientOrganizationId): string
    {
        $fileName = (string) $recipientOrganizationId . '-' . uniqid() . '.' . $extension;
        $relativePath = 'donation_agreements/' . (string) $accountId . '/' . $fileName;
        $absoluteDestination = $this->uploadsDir . '/' . $relativePath;

        if (!is_dir(dirname($absoluteDestination))) {
            mkdir(dirname($absoluteDestination), 0755, true);
        }

        rename($sourcePath, $absoluteDestination);

        return $relativePath;
    }
}
