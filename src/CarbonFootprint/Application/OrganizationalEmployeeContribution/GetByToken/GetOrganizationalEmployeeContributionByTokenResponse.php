<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken;

use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\AccountId;

final class GetOrganizationalEmployeeContributionByTokenResponse
{
    /**
     * @param list<Mobility> $mobilities
     * @param list<Property> $workplaces
     */
    public function __construct(
        public readonly OrganizationalEmployeeContribution $contribution,
        public readonly string $subjectName,
        public readonly int $year,
        public readonly ?AccountId $accountId,
        public readonly array $mobilities,
        public readonly array $workplaces,
    ) {}
}
