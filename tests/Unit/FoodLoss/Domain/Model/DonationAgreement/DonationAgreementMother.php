<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement;

use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class DonationAgreementMother
{
    public static function registeredDonationAgreement(): DonationAgreement
    {
        return self::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: (string) RecipientOrganizationId::generate(),
            accountId: 'account-id',
            startDate: new \DateTimeImmutable('2023-01-01'),
            expirationDate: new \DateTimeImmutable('2024-01-01'),
            uploadedBy: (string) UserId::generate(),
            path: 'path/to/file.pdf',
        );
    }

    public static function from(
        string $id,
        string $recipientOrganizationId,
        string $accountId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $expirationDate,
        string $uploadedBy,
        string $path,
    ): DonationAgreement {
        $donationAgreement = new DonationAgreement(
            id: new DonationAgreementId($id),
        );
        $donationAgreement->register(
            recipientOrganizationId: new RecipientOrganizationId($recipientOrganizationId),
            accountId: new AccountId($accountId),
            startDate: $startDate,
            expirationDate: $expirationDate,
            uploadedBy: new UserId($uploadedBy),
            path: $path,
        );

        return $donationAgreement;
    }

    public static function random(): DonationAgreement
    {
        return self::from(
            id: (string) DonationAgreementId::generate(),
            recipientOrganizationId: (string) RecipientOrganizationId::generate(),
            accountId: 'account-' . random_int(100, 999),
            startDate: new \DateTimeImmutable('NOW'),
            expirationDate: new \DateTimeImmutable('+1 year'),
            uploadedBy: (string) UserId::generate(),
            path: 'path/' . uniqid() . '.pdf',
        );
    }

    public static function forAccount(string $accountId): DonationAgreement
    {
        $agreement = self::random();

        return self::from(
            id: (string) $agreement->id(),
            recipientOrganizationId: (string) $agreement->recipientOrganizationId(),
            accountId: $accountId,
            startDate: $agreement->startDate(),
            expirationDate: $agreement->expirationDate(),
            uploadedBy: (string) $agreement->uploadedBy(),
            path: $agreement->path(),
        );
    }
}
