<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

final readonly class LegacyDonation
{
    public function __construct(
        public string $transactionUuid,
        public string $organizationId,
        public string $ecoProjectId,
        public float $amount,
        public string $currency,
        public string $donerName,
        public string $donerEmail,
        public \DateTimeImmutable $createdAt,
    ) {}
}
