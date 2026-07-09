<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo;

final class GetProviderContributionGeneralInfoRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}
}
