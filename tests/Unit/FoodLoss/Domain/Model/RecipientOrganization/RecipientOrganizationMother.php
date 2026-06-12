<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\RecipientOrganization;

use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationType;
use App\Security\Domain\Model\Account\AccountId;

class RecipientOrganizationMother
{
    public static function registeredRecipientOrganization(): RecipientOrganization
    {
        return self::fromName('Entidad Receptora');
    }

    public static function fromName(string $name): RecipientOrganization
    {
        return self::from(
            name: $name,
            id: (string) RecipientOrganizationId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): RecipientOrganization
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromAccount(string $account): RecipientOrganization
    {
        return self::from(
            name: 'name-' . random_int(100, 999),
            id: (string) RecipientOrganizationId::generate(),
            accountId: $account,
        );
    }

    public static function fromContact(string $name, string $email, string $phone): RecipientOrganization
    {
        $recipientOrganization = new RecipientOrganization(
            id: RecipientOrganizationId::generate(),
        );
        $recipientOrganization->register(
            name: 'Entidad Receptora',
            type: RecipientOrganizationType::FoodBank,
            nif: '12345678A',
            socialReason: 'Test Social Reason',
            address: 'Test Address',
            contactName: $name,
            contactEmail: $email,
            contactPhone: $phone,
            operatingHours: '9:00 - 17:00',
            pickupTypes: [],
            accountId: new AccountId('account-id-test'),
        );

        return $recipientOrganization;
    }

    public static function from(
        string $name,
        string $id,
        string $accountId = 'account-id-test',
        RecipientOrganizationType $type = RecipientOrganizationType::FoodBank,
    ): RecipientOrganization {
        $recipientOrganization = new RecipientOrganization(
            id: new RecipientOrganizationId($id),
        );
        $recipientOrganization->register(
            name: $name,
            type: $type,
            nif: '12345678A',
            socialReason: 'Test Social Reason',
            address: 'Test Address',
            contactName: 'Test Contact',
            contactEmail: 'contact@test.com',
            contactPhone: '+34 600 000 000',
            operatingHours: '9:00 - 17:00',
            pickupTypes: [],
            accountId: new AccountId($accountId),
        );

        return $recipientOrganization;
    }

    public static function random(): RecipientOrganization
    {
        return self::from(
            name: 'recipient-organization-' . random_int(100, 999),
            id: (string) RecipientOrganizationId::generate(),
        );
    }
}
