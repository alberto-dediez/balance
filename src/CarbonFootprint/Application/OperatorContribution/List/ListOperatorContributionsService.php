<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\List;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class ListOperatorContributionsService
{
    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
        private OperatorContributionAuthorizer $authorizer,
        private OperatorContributionRepository $contributionRepository,
        private OperatorContributionEmissionsCalculator $emissionsCalculator,
    ) {}

    public function __invoke(ListOperatorContributionsRequest $request, User $user): ListOperatorContributionsResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->companyCarbonFootprintAuthorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::View, $user);
        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::List, $user);

        $summaries = [];
        foreach ($this->contributionRepository->findActiveByCompanyCarbonFootprint($request->companyCarbonFootprintId) as $contribution) {
            $summaries[(string) $contribution->propertyId()] = $this->emissionsCalculator->summary($contribution);
        }

        return new ListOperatorContributionsResponse($summaries);
    }
}
