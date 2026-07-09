<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\AnswerGeneralInfo;

final class AnswerProviderContributionGeneralInfoRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly ?string $companyName,
        public readonly ?string $companyType,
        public readonly ?string $service,
        public readonly ?int $workersAssembly,
        public readonly ?int $workersCelebration,
        public readonly ?int $workersBreakdown,
    ) {}
}
