<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetOrganizationalEmployeeContributionDetailService
{
    public function __construct(
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private OrganizationalEmployeeContributionAuthorizer $authorizer,
        private EmployeeRepository $employeeRepository,
        private PropertyRepository $propertyRepository,
        private MobilityRepository $mobilityRepository,
    ) {}

    public function __invoke(GetOrganizationalEmployeeContributionDetailRequest $request, User $user): GetOrganizationalEmployeeContributionDetailResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);

        if (null === $contribution) {
            throw new \InvalidArgumentException('OrganizationalEmployeeContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::View, $user);

        $employeeId = $contribution->employeeId();
        $workplacePropertyId = $contribution->workplacePropertyId();

        $mobilitiesById = [];
        foreach ($this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0)) as $mobility) {
            $mobilitiesById[(string) $mobility->id()] = $mobility;
        }

        return new GetOrganizationalEmployeeContributionDetailResponse(
            contribution: $contribution,
            employee: null !== $employeeId ? $this->employeeRepository->find($employeeId) : null,
            workplace: null !== $workplacePropertyId ? $this->propertyRepository->find($workplacePropertyId) : null,
            mobilitiesById: $mobilitiesById,
        );
    }
}
