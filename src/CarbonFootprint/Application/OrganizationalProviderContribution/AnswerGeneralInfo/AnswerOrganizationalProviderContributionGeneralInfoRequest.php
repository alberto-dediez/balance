<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerGeneralInfo;

final class AnswerOrganizationalProviderContributionGeneralInfoRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly ?string $companyName,
        public readonly ?string $service,
        public readonly string $consentIp = '',
        public readonly string $consentUserAgent = '',
        public readonly string $consentAcceptLanguage = '',
    ) {}
}
