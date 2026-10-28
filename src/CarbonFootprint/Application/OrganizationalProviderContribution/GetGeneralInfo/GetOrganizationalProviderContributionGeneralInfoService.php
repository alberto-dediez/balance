<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\GetGeneralInfo;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyRepository;

final class GetOrganizationalProviderContributionGeneralInfoService
{
    public function __construct(
        private OrganizationalProviderContributionAuthenticator $authenticator,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
    ) {}

    public function __invoke(GetOrganizationalProviderContributionGeneralInfoRequest $request): GetOrganizationalProviderContributionGeneralInfoResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $located = $this->authenticator->locateCarbonFootprint($authentication);
        $located->ensureIsEditable();
        $carbonFootprint = $located->carbonFootprint;

        if ($carbonFootprint instanceof CompanyCarbonFootprint) {
            $companyId = $carbonFootprint->companyId();
            $subjectName = null !== $companyId ? $this->companyRepository->find($companyId)?->name() : null;
        } else {
            $propertyId = $carbonFootprint->propertyId();
            $subjectName = null !== $propertyId ? $this->propertyRepository->find($propertyId)?->name() : null;
        }

        if (null === $subjectName) {
            throw new OrganizationalProviderContributionTokenInvalidException();
        }

        return new GetOrganizationalProviderContributionGeneralInfoResponse(
            $authentication->contribution,
            $subjectName,
            $carbonFootprint->year(),
        );
    }
}
