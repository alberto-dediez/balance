<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

final class EmployeeContributionsAggregator
{
    /**
     * @param list<EmployeeContribution> $contributions
     * @param array<string, FactorBasis> $factorBasisByMobilityId
     */
    public function aggregate(array $contributions, int $totalEmployees, array $factorBasisByMobilityId = []): EmployeeContributionsAggregation
    {
        $count = count($contributions);

        if (0 === $count) {
            return new EmployeeContributionsAggregation(kmByMobilityIdByPhase: [], hotelNightsByPhase: []);
        }

        $kmByMobilityIdByPhase = [];
        $hotelNightsByPhase = [];

        foreach ($contributions as $contribution) {
            $this->aggregatePhase(
                $kmByMobilityIdByPhase,
                $hotelNightsByPhase,
                Phase::Assembly,
                $contribution->workedAssembly(),
                $contribution->assemblyMobilityId(),
                $contribution->assemblyDistanceKm(),
                $contribution->assemblyVehicleOccupants(),
                $contribution->assemblyDays(),
                $contribution->assemblyHotelNights(),
                $factorBasisByMobilityId,
            );

            $this->aggregatePhase(
                $kmByMobilityIdByPhase,
                $hotelNightsByPhase,
                Phase::Celebration,
                $contribution->workedCelebration(),
                $contribution->celebrationMobilityId(),
                $contribution->celebrationDistanceKm(),
                $contribution->celebrationVehicleOccupants(),
                $contribution->celebrationDays(),
                $contribution->celebrationHotelNights(),
                $factorBasisByMobilityId,
            );

            $this->aggregatePhase(
                $kmByMobilityIdByPhase,
                $hotelNightsByPhase,
                Phase::Breakdown,
                $contribution->workedBreakdown(),
                $contribution->breakdownMobilityId(),
                $contribution->breakdownDistanceKm(),
                $contribution->breakdownVehicleOccupants(),
                $contribution->breakdownDays(),
                $contribution->breakdownHotelNights(),
                $factorBasisByMobilityId,
            );
        }

        $extrapolationFactor = $totalEmployees / $count;

        foreach ($kmByMobilityIdByPhase as $phaseValue => $kmByMobilityId) {
            $kmByMobilityIdByPhase[$phaseValue] = array_map(
                static fn(float $km): float => $km * $extrapolationFactor,
                $kmByMobilityId,
            );
        }

        $hotelNightsByPhase = array_map(
            static fn(float $nights): float => $nights * $extrapolationFactor,
            $hotelNightsByPhase,
        );

        return new EmployeeContributionsAggregation(
            kmByMobilityIdByPhase: $kmByMobilityIdByPhase,
            hotelNightsByPhase: $hotelNightsByPhase,
        );
    }

    /**
     * @param array<string, array<string, float>> $kmByMobilityIdByPhase
     * @param array<string, float>                $hotelNightsByPhase
     * @param array<string, FactorBasis>          $factorBasisByMobilityId
     */
    private function aggregatePhase(
        array &$kmByMobilityIdByPhase,
        array &$hotelNightsByPhase,
        Phase $phase,
        bool $worked,
        ?MobilityId $mobilityId,
        ?float $distanceKm,
        ?int $vehicleOccupants,
        ?int $days,
        ?int $hotelNights,
        array $factorBasisByMobilityId = [],
    ): void {
        if (!$worked) {
            return;
        }

        if (null !== $mobilityId && null !== $distanceKm && null !== $days) {
            $factorBasis = $factorBasisByMobilityId[(string) $mobilityId] ?? FactorBasis::PerPassenger;
            $km = $factorBasis->kmCoveringOneOccupant($distanceKm * $days, $vehicleOccupants ?? 1);

            $kmByMobilityIdByPhase[$phase->value][(string) $mobilityId] = ($kmByMobilityIdByPhase[$phase->value][(string) $mobilityId] ?? 0.0) + $km;
        }

        if (null !== $hotelNights && $hotelNights > 0) {
            $hotelNightsByPhase[$phase->value] = ($hotelNightsByPhase[$phase->value] ?? 0.0) + $hotelNights;
        }
    }
}
