<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerBusinessTravel;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\TripAnswer;

final class AnswerOrganizationalEmployeeContributionBusinessTravelRequest
{
    /** @param list<TripAnswer> $trips */
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly array $trips,
    ) {}
}
