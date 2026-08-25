<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility;

use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;

final class MobilityFactorBasisProvider
{
    public function __construct(private MobilityRepository $mobilityRepository) {}

    /** @return array<string, FactorBasis> */
    public function factorBasisByMobilityId(): array
    {
        $factorBasisByMobilityId = [];

        foreach ($this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0)) as $mobility) {
            $factorBasisByMobilityId[(string) $mobility->id()] = $mobility->factorBasis();
        }

        return $factorBasisByMobilityId;
    }
}
