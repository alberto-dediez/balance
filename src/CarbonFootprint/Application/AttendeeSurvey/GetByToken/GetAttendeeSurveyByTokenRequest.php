<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetByToken;

final class GetAttendeeSurveyByTokenRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
    ) {}
}
