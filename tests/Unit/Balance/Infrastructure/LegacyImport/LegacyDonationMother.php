<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\LegacyImport;

use App\Balance\Infrastructure\LegacyImport\LegacyDonation;

class LegacyDonationMother
{
    public static function from(string $organizationId, string $ecoProjectId, float $amount = 50.0, ?string $transactionUuid = null): LegacyDonation
    {
        return new LegacyDonation(
            transactionUuid: $transactionUuid ?? 'c0063a20-fe4e-48c6-b91a-fed92e6e4ebc',
            organizationId: $organizationId,
            ecoProjectId: $ecoProjectId,
            amount: $amount,
            currency: 'eur',
            donerName: 'Ana Donante',
            donerEmail: 'ana@example.com',
            createdAt: new \DateTimeImmutable('2024-11-11 15:07:10'),
        );
    }
}
