<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetCompanyCarbonFootprintScopeService
{
    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private StageSectionsBuilder $sectionsBuilder,
    ) {}

    public function __invoke(GetCompanyCarbonFootprintScopeRequest $request, User $user): GetCompanyCarbonFootprintScopeResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::View, $user);

        $companyId = $companyCarbonFootprint->companyId();
        $properties = null !== $companyId ? array_values($this->propertyRepository->findByCompanyId($companyId)) : [];

        usort($properties, static fn(Property $a, Property $b) => strcasecmp((string) $a->name(), (string) $b->name()));

        $blocks = [new PropertyStageSections(
            null,
            $this->sectionsBuilder->build($companyCarbonFootprint, $request->scope)->sections,
        )];

        foreach ($properties as $property) {
            $blocks[] = new PropertyStageSections(
                $property,
                $this->sectionsBuilder->build($companyCarbonFootprint, $request->scope, propertyId: $property->id())->sections,
            );
        }

        return new GetCompanyCarbonFootprintScopeResponse($blocks, $companyCarbonFootprint->exclusionsFor($request->scope));
    }
}
