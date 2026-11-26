<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseBilling;

class CreditPurchaseBillingMother
{
    public static function random(): CreditPurchaseBilling
    {
        return self::from();
    }

    public static function from(
        string $legalName = 'Mi Empresa SL',
        string $taxId = 'B12345678',
        string $street = 'Calle Mayor 1',
        string $postalCode = '28001',
        string $city = 'Madrid',
        string $country = 'ESP',
        string $email = 'facturas@example.com',
    ): CreditPurchaseBilling {
        return new CreditPurchaseBilling(
            legalName: $legalName,
            taxId: $taxId,
            street: $street,
            postalCode: $postalCode,
            city: $city,
            country: $country,
            email: $email,
        );
    }
}
