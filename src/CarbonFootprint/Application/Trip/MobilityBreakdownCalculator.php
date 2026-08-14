<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;

final class MobilityBreakdownCalculator
{
    public function __construct(
        private MobilityRepository $mobilityRepository,
        private ActivityEmissionsResolver $activityEmissionsResolver,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    /**
     * @param array<string, float> $kmByMobilityId
     *
     * @return list<MobilityBreakdownRow>
     */
    public function calculate(array $kmByMobilityId, ?EmissionFactorGroupId $emissionFactorGroupId, ?int $calculationYear): array
    {
        $emissionFactorGroupId ??= $this->emissionFactorGroupRepository->findDefault()?->id();
        $calculationYear ??= (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $rows = [];

        foreach ($kmByMobilityId as $mobilityId => $km) {
            $mobility = $this->mobilityRepository->find(new MobilityId($mobilityId));

            if (null === $mobility) {
                continue;
            }

            $rows[] = new MobilityBreakdownRow(
                mobility: $mobility,
                km: $km,
                emissions: $this->emissionsFor($mobilityId, $km, $emissionFactorGroupId, $calculationYear),
            );
        }

        usort($rows, static fn(MobilityBreakdownRow $a, MobilityBreakdownRow $b): int => $b->km <=> $a->km);

        return $rows;
    }

    private function emissionsFor(string $mobilityId, float $km, ?EmissionFactorGroupId $emissionFactorGroupId, int $calculationYear): Emissions
    {
        if (null === $emissionFactorGroupId || $km <= 0.0) {
            return new Emissions();
        }

        try {
            return $this->activityEmissionsResolver->resolveForGroupAndYear(
                $emissionFactorGroupId,
                $calculationYear,
                ActivityType::Mobility,
                $mobilityId,
                null,
                new Quantity($km, Unit::Kilometer),
                null,
            )->emissions;
        } catch (\InvalidArgumentException) {
            return new Emissions();
        }
    }
}
