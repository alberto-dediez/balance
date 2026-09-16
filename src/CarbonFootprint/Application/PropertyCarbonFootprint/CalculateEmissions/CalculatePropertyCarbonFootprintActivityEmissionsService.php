<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsResponse;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class CalculatePropertyCarbonFootprintActivityEmissionsService
{
    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private ActivityEmissionsResolver $emissionsResolver,
    ) {}

    public function __invoke(CalculatePropertyCarbonFootprintActivityEmissionsRequest $request, User $user): CalculateActivityEmissionsResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::View, $user);

        return $this->emissionsResolver->resolve(
            $propertyCarbonFootprint,
            $request->activityType,
            $request->itemId,
            $request->condition,
            $request->quantity,
            $request->secondaryItemId,
        );
    }
}
