<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerCommuting;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\CommuteAnswer;
use App\Property\Domain\Model\Property\PropertyId;

final class AnswerOrganizationalEmployeeContributionCommutingRequest
{
    /** @param list<CommuteAnswer> $commutes */
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly ?PropertyId $workplacePropertyId,
        public readonly ?int $weeksPerYear,
        public readonly array $commutes,
        public readonly bool $consentGranted = false,
        public readonly ?string $consentIp = null,
        public readonly ?string $consentUserAgent = null,
        public readonly ?string $consentAcceptLanguage = null,
    ) {}
}
