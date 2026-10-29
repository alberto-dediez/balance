<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerBusinessTravel;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;

final class AnswerOrganizationalEmployeeContributionBusinessTravelService
{
    public function __construct(
        private OrganizationalEmployeeContributionAuthenticator $authenticator,
        private OrganizationalEmployeeContributionRepository $contributionRepository,
        private OrganizationalCarbonFootprintLocator $locator,
    ) {}

    public function __invoke(AnswerOrganizationalEmployeeContributionBusinessTravelRequest $request): void
    {
        $contribution = $this->authenticator->authenticate($request->token)->contribution;

        try {
            $carbonFootprint = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId())->carbonFootprint;
        } catch (\InvalidArgumentException) {
            throw new OrganizationalEmployeeContributionTokenInvalidException();
        }

        if ($carbonFootprint->isCompleted()) {
            throw new OrganizationalEmployeeContributionClosedException();
        }

        $contribution->answerBusinessTravel($request->trips);

        $this->contributionRepository->add($contribution);
    }
}
