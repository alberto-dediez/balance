<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\GetGeneralInfo;

final class GetOperatorContributionGeneralInfoRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}
}
