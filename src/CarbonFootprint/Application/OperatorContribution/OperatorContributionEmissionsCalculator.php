<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class OperatorContributionEmissionsCalculator
{
    public function __construct(private EmissionsBreakdownBuilder $breakdownBuilder) {}

    /** @return array{scope_1: Emissions, scope_2: Emissions} */
    public function byScope(OperatorContribution $contribution): array
    {
        $breakdown = $this->breakdownBuilder->build($contribution, [Scope::Scope1, Scope::Scope2]);

        return [
            'scope_1' => $breakdown->byStage[Scope::Scope1->key()] ?? new Emissions(),
            'scope_2' => $breakdown->byStage[Scope::Scope2->key()] ?? new Emissions(),
        ];
    }

    public function scope1And2(OperatorContribution $contribution): Emissions
    {
        $byScope = $this->byScope($contribution);

        return $byScope['scope_1']->add($byScope['scope_2']);
    }

    public function summary(OperatorContribution $contribution): OperatorContributionSummary
    {
        $byScope = $this->byScope($contribution);

        return new OperatorContributionSummary(
            contribution: $contribution,
            scope1: $byScope['scope_1'],
            scope2: $byScope['scope_2'],
            total: $byScope['scope_1']->add($byScope['scope_2']),
        );
    }
}
