<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\GetByToken;

final class GetEmployeeContributionByTokenRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}
}
