<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\Answer;

final class AnswerProviderContributionRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}
}
