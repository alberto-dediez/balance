<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\AnswerGeneralInfo;

final class AnswerOperatorContributionGeneralInfoRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly ?string $contactName,
        public readonly bool $precalculated,
        public readonly string $consentIp = '',
        public readonly string $consentUserAgent = '',
        public readonly string $consentAcceptLanguage = '',
    ) {}
}
