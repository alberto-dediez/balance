<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetPropertyCarbonFootprintScopeService
{
    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private StageSectionsBuilder $sectionsBuilder,
    ) {}

    public function __invoke(GetPropertyCarbonFootprintScopeRequest $request, User $user): GetPropertyCarbonFootprintScopeResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::View, $user);

        $stageSections = $this->sectionsBuilder->build($propertyCarbonFootprint, $request->scope);

        return new GetPropertyCarbonFootprintScopeResponse($stageSections->sections, $stageSections->exclusions);
    }
}
