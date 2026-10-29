<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;

final class OrganizationalEmployeeContributionAuthenticator
{
    public function __construct(
        private OrganizationalEmployeeContributionRepository $contributionRepository,
    ) {}

    public function authenticate(#[\SensitiveParameter] string $token): OrganizationalEmployeeContributionAuthentication
    {
        $contribution = $this->contributionRepository->findByToken($token);

        if (null === $contribution) {
            throw new OrganizationalEmployeeContributionTokenInvalidException();
        }

        if ($contribution->status()->isIgnored()) {
            throw new OrganizationalEmployeeContributionIgnoredException();
        }

        return new OrganizationalEmployeeContributionAuthentication($contribution);
    }
}
