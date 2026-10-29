<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;

final class GetOrganizationalEmployeeContributionByTokenService
{
    public function __construct(
        private OrganizationalEmployeeContributionAuthenticator $authenticator,
        private OrganizationalCarbonFootprintLocator $locator,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
        private CompanyProperties $companyProperties,
        private MobilityRepository $mobilityRepository,
    ) {}

    public function __invoke(GetOrganizationalEmployeeContributionByTokenRequest $request): GetOrganizationalEmployeeContributionByTokenResponse
    {
        $contribution = $this->authenticator->authenticate($request->token)->contribution;

        try {
            $carbonFootprint = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId())->carbonFootprint;
        } catch (\InvalidArgumentException) {
            throw new OrganizationalEmployeeContributionTokenInvalidException();
        }

        if ($carbonFootprint instanceof CompanyCarbonFootprint) {
            $companyId = $carbonFootprint->companyId();
            $subjectName = null !== $companyId ? $this->companyRepository->find($companyId)?->name() : null;
            $workplaces = $this->companyProperties->consolidated($companyId);
        } else {
            $propertyId = $carbonFootprint->propertyId();
            $subjectName = null !== $propertyId ? $this->propertyRepository->find($propertyId)?->name() : null;
            $workplaces = [];
        }

        if (null === $subjectName) {
            throw new OrganizationalEmployeeContributionTokenInvalidException();
        }

        return new GetOrganizationalEmployeeContributionByTokenResponse(
            contribution: $contribution,
            subjectName: $subjectName,
            year: $carbonFootprint->year(),
            accountId: $carbonFootprint->accountId(),
            mobilities: array_values($this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0))),
            workplaces: $workplaces,
        );
    }
}
