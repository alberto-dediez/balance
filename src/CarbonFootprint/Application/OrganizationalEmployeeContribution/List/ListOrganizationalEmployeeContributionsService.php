<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\EmployeeContributionEmissionsCalculator;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionsOverview;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class ListOrganizationalEmployeeContributionsService
{
    public function __construct(
        private OrganizationalCarbonFootprintLocator $locator,
        private OrganizationalEmployeeParticipationRepository $participationRepository,
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private EmployeeRepository $employeeRepository,
        private PropertyRepository $propertyRepository,
        private EmployeeContributionEmissionsCalculator $emissionsCalculator,
    ) {}

    public function __invoke(ListOrganizationalEmployeeContributionsRequest $request, User $user): OrganizationalEmployeeContributionsOverview
    {
        $located = $this->locator->locate($request->subjectType, $request->carbonFootprintId);

        $located->denyUnlessCanDoThat(SecurityAction::View, $user);

        $participation = $this->participationRepository->findByCarbonFootprint($request->subjectType, $request->carbonFootprintId);
        $contributions = $this->contributionRepository->findByCarbonFootprint($request->subjectType, $request->carbonFootprintId);

        $employeesById = [];
        $propertyIds = [];
        $pendingCount = 0;
        $answeredCount = 0;
        $ignoredCount = 0;

        foreach ($contributions as $contribution) {
            $employeeId = $contribution->employeeId();

            if (null !== $employeeId) {
                $employee = $this->employeeRepository->find($employeeId);

                if (null !== $employee) {
                    $employeesById[(string) $employeeId] = $employee;
                }
            }

            $workplacePropertyId = $contribution->workplacePropertyId();

            if (null !== $workplacePropertyId) {
                $propertyIds[(string) $workplacePropertyId] = $workplacePropertyId;
            }

            match (true) {
                $contribution->status()->isAnswered() => ++$answeredCount,
                $contribution->status()->isIgnored() => ++$ignoredCount,
                default => ++$pendingCount,
            };
        }

        $emissionsBySourceAndBlock = $this->emissionsCalculator->emissionsBySourceAndBlock($located->carbonFootprint);

        foreach ($emissionsBySourceAndBlock as $emissionsByBlock) {
            foreach (array_keys($emissionsByBlock) as $block) {
                if ('' !== (string) $block) {
                    $propertyIds[(string) $block] = new PropertyId((string) $block);
                }
            }
        }

        $propertiesById = [];

        foreach ($propertyIds as $key => $propertyId) {
            $property = $this->propertyRepository->find($propertyId);

            if (null !== $property) {
                $propertiesById[(string) $key] = $property;
            }
        }

        $totalEmployees = $participation?->totalEmployees();
        $answeredPercentage = null !== $totalEmployees && $totalEmployees > 0
            ? min(100.0, ($answeredCount / $totalEmployees) * 100.0)
            : null;

        return new OrganizationalEmployeeContributionsOverview(
            participation: $participation,
            contributions: $contributions,
            employeesById: $employeesById,
            pendingCount: $pendingCount,
            answeredCount: $answeredCount,
            ignoredCount: $ignoredCount,
            answeredPercentage: $answeredPercentage,
            emissionsBySourceAndBlock: $emissionsBySourceAndBlock,
            propertiesById: $propertiesById,
        );
    }
}
