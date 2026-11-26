<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Request;

final class CreditPurchaseBillingDefaultsResponse
{
    public function __construct(
        public readonly string $legalName,
        public readonly string $taxId,
        public readonly string $street,
        public readonly string $postalCode,
        public readonly string $city,
        public readonly string $country,
        public readonly string $email,
    ) {}
}
