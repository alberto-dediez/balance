<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\GetGeneralInfo;

use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionTokenInvalidException;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;

final class GetOperatorContributionGeneralInfoService
{
    public function __construct(
        private OperatorContributionAuthenticator $authenticator,
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
    ) {}

    public function __invoke(GetOperatorContributionGeneralInfoRequest $request): GetOperatorContributionGeneralInfoResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $companyCarbonFootprint = $this->authenticator->locateCompanyCarbonFootprint($authentication);
        $this->authenticator->ensureEditable($companyCarbonFootprint);

        $contribution = $authentication->contribution;
        $propertyId = $contribution->propertyId();
        $companyId = $companyCarbonFootprint->companyId();

        $propertyName = $propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId)?->name() : null;
        $companyName = $companyId instanceof CompanyId ? $this->companyRepository->find($companyId)?->name() : null;

        if (null === $propertyName || null === $companyName) {
            throw new OperatorContributionTokenInvalidException();
        }

        return new GetOperatorContributionGeneralInfoResponse(
            contribution: $contribution,
            propertyName: $propertyName,
            companyName: $companyName,
            year: $companyCarbonFootprint->year(),
        );
    }
}
