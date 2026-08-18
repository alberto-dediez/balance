<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution;

final class OwnerContributorDefaultsResponse
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $country,
        public readonly bool $isCompany,
        public readonly ?string $companyName,
        public readonly ?string $vatNumber,
    ) {}
}
