<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsResponse;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class CalculateCompanyCarbonFootprintActivityEmissionsService
{
    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private ActivityEmissionsResolver $emissionsResolver,
    ) {}

    public function __invoke(CalculateCompanyCarbonFootprintActivityEmissionsRequest $request, User $user): CalculateActivityEmissionsResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::View, $user);

        return $this->emissionsResolver->resolve(
            $companyCarbonFootprint,
            $request->activityType,
            $request->itemId,
            $request->condition,
            $request->quantity,
            $request->secondaryItemId,
        );
    }
}
