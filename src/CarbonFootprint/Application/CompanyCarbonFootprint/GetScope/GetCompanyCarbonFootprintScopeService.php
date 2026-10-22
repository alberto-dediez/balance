<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyEmissionsResolver;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetCompanyCarbonFootprintScopeService
{
    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyProperties $companyProperties,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private StageSectionsBuilder $sectionsBuilder,
        private DerivedSourceEmissionsCalculator $derivedSourceEmissionsCalculator,
        private NonConsolidatedPropertyEmissionsResolver $nonConsolidatedPropertyEmissionsResolver,
    ) {}

    public function __invoke(GetCompanyCarbonFootprintScopeRequest $request, User $user): GetCompanyCarbonFootprintScopeResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::View, $user);

        $properties = $this->companyProperties->consolidated($companyCarbonFootprint->companyId());

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

        return new GetCompanyCarbonFootprintScopeResponse(
            blocks: $blocks,
            exclusions: $companyCarbonFootprint->exclusionsFor($request->scope),
            stageExclusion: $this->stageExclusion($companyCarbonFootprint, $request->scope),
            derivedEmissions: $this->derivedSourceEmissionsCalculator->calculate($companyCarbonFootprint, $request->scope),
            derivedLines: $this->derivedSourceEmissionsCalculator->lines($companyCarbonFootprint, $request->scope),
            nonConsolidatedProperties: Scope::Scope3 === $request->scope ? $this->nonConsolidatedProperties($companyCarbonFootprint) : [],
        );
    }

    /** @return list<NonConsolidatedPropertyRow> */
    private function nonConsolidatedProperties(CompanyCarbonFootprint $companyCarbonFootprint): array
    {
        if ($companyCarbonFootprint->isCompleted()) {
            $propertiesById = [];
            foreach ($this->companyProperties->all($companyCarbonFootprint->companyId()) as $property) {
                $propertiesById[(string) $property->id()] = $property;
            }

            $rows = [];
            foreach ($companyCarbonFootprint->nonConsolidatedPropertyLines() as $line) {
                $rows[] = new NonConsolidatedPropertyRow(
                    property: $propertiesById[(string) $line->propertyId] ?? null,
                    propertyId: $line->propertyId,
                    source: $line->source,
                    originId: $line->propertyCarbonFootprintId,
                    year: $companyCarbonFootprint->year(),
                    calculatedEmissions: $line->calculatedEmissions,
                    manualCo2eq: $line->manualCo2eq,
                    emissions: $line->emissions,
                );
            }

            return $rows;
        }

        $manualByProperty = [];
        foreach ($companyCarbonFootprint->nonConsolidatedPropertyLines() as $line) {
            $manualByProperty[(string) $line->propertyId] = $line->manualCo2eq;
        }

        $rows = [];
        foreach ($this->nonConsolidatedPropertyEmissionsResolver->resolve($companyCarbonFootprint) as $resolved) {
            $manualCo2eq = $manualByProperty[(string) $resolved->property->id()] ?? null;

            $rows[] = new NonConsolidatedPropertyRow(
                property: $resolved->property,
                propertyId: $resolved->property->id(),
                source: $resolved->source,
                originId: $resolved->origin?->id(),
                year: $companyCarbonFootprint->year(),
                calculatedEmissions: $resolved->calculatedEmissions,
                manualCo2eq: $manualCo2eq,
                emissions: null !== $manualCo2eq ? new Emissions($manualCo2eq) : $resolved->calculatedEmissions,
            );
        }

        return $rows;
    }

    private function stageExclusion(CompanyCarbonFootprint $companyCarbonFootprint, Scope $scope): ?StageExclusionAnswer
    {
        $reason = $companyCarbonFootprint->stageExclusionReason($scope);

        if (!$companyCarbonFootprint->isStageExcluded($scope) || null === $reason) {
            return null;
        }

        return new StageExclusionAnswer($reason, $companyCarbonFootprint->stageExclusionComment($scope));
    }
}
