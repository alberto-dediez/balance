<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\GetData;

final class GetOperatorContributionDataRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}
}
