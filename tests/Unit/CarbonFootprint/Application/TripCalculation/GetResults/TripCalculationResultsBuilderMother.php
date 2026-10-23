<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculation\GetResults;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownCalculator;
use App\CarbonFootprint\Application\Trip\OriginFlowsCalculator;
use App\CarbonFootprint\Application\Trip\PerPersonTripEmissionsCalculator;
use App\CarbonFootprint\Application\Trip\TripEmissionsCalculator;
use App\CarbonFootprint\Application\TripCalculation\GetResults\TripCalculationResultsBuilder;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregator;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;

class TripCalculationResultsBuilderMother
{
    public static function create(
        InMemoryTripCalculationRepository $tripCalculationRepository,
        InMemoryProjectContributionRepository $projectContributionRepository,
        InMemoryMobilityRepository $mobilityRepository = new InMemoryMobilityRepository(),
    ): TripCalculationResultsBuilder {
        return new TripCalculationResultsBuilder(
            $tripCalculationRepository,
            new TripCalculationsAggregator(new TripLegsAccumulator()),
            new MobilityBreakdownCalculator(
                $mobilityRepository,
                new ActivityEmissionsResolver(
                    new EmissionFactorSelectorRegistry([new MobilityEmissionFactorSelector($mobilityRepository)]),
                    EmissionsCalculatorMother::create(),
                ),
                new InMemoryEmissionFactorGroupRepository(),
            ),
            $projectContributionRepository,
            new ProjectContributionsAggregator(),
            new MobilityFactorBasisProvider($mobilityRepository),
            new PerPersonTripEmissionsCalculator(
                new TripLegsAccumulator(),
                new MobilityFactorBasisProvider($mobilityRepository),
                new TripEmissionsCalculator(
                    new ActivityEmissionsResolver(
                        new EmissionFactorSelectorRegistry([new MobilityEmissionFactorSelector($mobilityRepository)]),
                        EmissionsCalculatorMother::create(),
                    ),
                    new InMemoryOvernightStayRepository(),
                ),
            ),
            new OriginFlowsCalculator(),
        );
    }
}
