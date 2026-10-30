<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\AccountId;

class NonConsolidatedPropertyEmissionsResolver
{
    public function __construct(
        private CompanyProperties $companyProperties,
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private EmissionsBreakdownBuilder $breakdownBuilder,
        private OperatorContributionRepository $operatorContributionRepository,
        private OperatorContributionEmissionsCalculator $operatorContributionEmissionsCalculator,
    ) {}

    /** @return list<NonConsolidatedPropertyEmissions> */
    public function resolve(CompanyCarbonFootprint $companyCarbonFootprint): array
    {
        $resolved = [];

        foreach ($this->companyProperties->nonConsolidated($companyCarbonFootprint->companyId()) as $property) {
            $source = Scope::sourceForOperatingRegime($property->operatingRegime());

            if (null === $source) {
                continue;
            }

            $origin = $this->origin($property, $companyCarbonFootprint);
            $contribution = $this->operatorContributionRepository->findActiveByCompanyCarbonFootprintAndProperty($companyCarbonFootprint->id(), $property->id());
            $fromOperatorContribution = false;
            $calculatedEmissions = new Emissions();

            if (null !== $origin) {
                $calculatedEmissions = $this->scope1And2Emissions($origin);
            } elseif (null !== $contribution && $contribution->status()->isAnswered()) {
                $fromOperatorContribution = true;
                $calculatedEmissions = $this->operatorContributionEmissionsCalculator->scope1And2($contribution);
            }

            $resolved[] = new NonConsolidatedPropertyEmissions(
                property: $property,
                source: $source,
                origin: $origin,
                calculatedEmissions: $calculatedEmissions,
                contribution: $contribution,
                fromOperatorContribution: $fromOperatorContribution,
            );
        }

        return $resolved;
    }

    private function origin(Property $property, CompanyCarbonFootprint $companyCarbonFootprint): ?PropertyCarbonFootprint
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->findByPropertyIdAndYear($property->id(), $companyCarbonFootprint->year());

        if (null === $propertyCarbonFootprint || $propertyCarbonFootprint->isRemoved() || !$propertyCarbonFootprint->isCompleted()) {
            return null;
        }

        $accountId = $propertyCarbonFootprint->accountId();
        $companyAccountId = $companyCarbonFootprint->accountId();

        if (!$accountId instanceof AccountId || !$companyAccountId instanceof AccountId || !$accountId->equals($companyAccountId)) {
            return null;
        }

        return $propertyCarbonFootprint;
    }

    private function scope1And2Emissions(PropertyCarbonFootprint $propertyCarbonFootprint): Emissions
    {
        $breakdown = $this->breakdownBuilder->build($propertyCarbonFootprint, [Scope::Scope1, Scope::Scope2]);

        return ($breakdown->byStage[Scope::Scope1->key()] ?? new Emissions())
            ->add($breakdown->byStage[Scope::Scope2->key()] ?? new Emissions());
    }
}
