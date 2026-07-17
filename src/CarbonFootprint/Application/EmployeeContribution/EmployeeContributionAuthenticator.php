<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionTokenInvalidException;

final class EmployeeContributionAuthenticator
{
    public function __construct(
        private EmployeeContributionRepository $employeeContributionRepository,
    ) {}

    public function authenticate(#[\SensitiveParameter] string $token): EmployeeContributionAuthentication
    {
        $employeeContribution = $this->employeeContributionRepository->findByToken($token);

        if (null === $employeeContribution) {
            throw new EmployeeContributionTokenInvalidException();
        }

        if ($employeeContribution->status()->isIgnored()) {
            throw new EmployeeContributionIgnoredException();
        }

        return new EmployeeContributionAuthentication($employeeContribution);
    }
}
