<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;

final class TripEmissionsCalculator
{
    public function __construct(
        private ActivityEmissionsResolver $activityEmissionsResolver,
        private OvernightStayRepository $overnightStayRepository,
    ) {}

    /** @param array<string, float> $kmByMobilityId */
    public function calculate(
        array $kmByMobilityId,
        float $nights,
        EmissionFactorGroupId $emissionFactorGroupId,
        int $year,
    ): TripEmissions {
        $mobilityEmissions = new Emissions();

        foreach ($kmByMobilityId as $mobilityId => $km) {
            if ($km <= 0.0) {
                continue;
            }

            $mobilityEmissions = $mobilityEmissions->add(
                $this->activityEmissionsResolver->resolveForGroupAndYear(
                    $emissionFactorGroupId,
                    $year,
                    ActivityType::Mobility,
                    $mobilityId,
                    null,
                    new Quantity($km, Unit::Kilometer),
                    null,
                )->emissions,
            );
        }

        if ($nights <= 0.0) {
            return new TripEmissions($mobilityEmissions, new Emissions());
        }

        $overnightStays = $this->overnightStayRepository->findByFilter(
            new OvernightStayFilter(category: CategoryOvernight::Attendees, limit: 1),
        );
        $overnightStay = $overnightStays[0] ?? null;

        if (null === $overnightStay) {
            return new TripEmissions($mobilityEmissions, new Emissions(), overnightStayCatalogMissing: true);
        }

        $overnightEmissions = $this->activityEmissionsResolver->resolveForGroupAndYear(
            $emissionFactorGroupId,
            $year,
            ActivityType::OvernightStay,
            (string) $overnightStay->id(),
            null,
            new Quantity($nights, Unit::Pernoctacion),
            null,
        )->emissions;

        return new TripEmissions($mobilityEmissions, $overnightEmissions);
    }
}
