<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

class DeliveryLogMother
{
    public static function registeredDeliveryLog(): DeliveryLog
    {
        return self::from(
            productType: 'Apples',
            id: (string) DeliveryLogId::generate(),
            accountId: 'account-id-test',
        );
    }

    public static function from(
        string $productType,
        string $id,
        string $accountId,
        string $recipientOrganizationId = 'recipient-org-id-test',
        string $propertyId = 'property-id-test',
        float $quantity = 10.0,
        DeliveryLogUnit $unit = DeliveryLogUnit::Kg,
        string $batch = 'B123',
        ?\DateTimeImmutable $date = null,
    ): DeliveryLog {
        $deliveryLog = new DeliveryLog(
            id: new DeliveryLogId($id),
            accountId: new AccountId($accountId),
        );
        $deliveryLog->register(
            date: $date ?? new \DateTimeImmutable('2026-04-06'),
            quantity: $quantity,
            unit: $unit,
            productType: $productType,
            batch: $batch,
            recipientOrganizationId: new RecipientOrganizationId($recipientOrganizationId),
            accountId: new AccountId($accountId),
            propertyId: new PropertyId($propertyId),
        );

        return $deliveryLog;
    }

    public static function random(): DeliveryLog
    {
        return self::from(
            productType: 'Product-' . rand(100, 999),
            id: (string) DeliveryLogId::generate(),
            accountId: (string) AccountId::generate(),
        );
    }

    public static function withPropertyId(PropertyId $propertyId): DeliveryLog
    {
        return self::from(
            productType: 'Product-' . rand(100, 999),
            id: (string) DeliveryLogId::generate(),
            accountId: (string) AccountId::generate(),
            propertyId: (string) $propertyId,
        );
    }

    public static function withRecipientOrganizationId(RecipientOrganizationId $recipientOrganizationId): DeliveryLog
    {
        return self::from(
            productType: 'Product-' . rand(100, 999),
            id: (string) DeliveryLogId::generate(),
            accountId: (string) AccountId::generate(),
            recipientOrganizationId: (string) $recipientOrganizationId,
        );
    }
}
