<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\EmissionFactor\List;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private EconomicSectorRepository $repository,
        private EconomicSectorAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new EconomicSector(id: EconomicSectorId::generate()), SecurityAction::List, $user);

        $economicSectors = $this->repository->findByFilter(new EconomicSectorFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($economicSectors, $request->emissionFactorGroupId) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            economicSectors: $economicSectors,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
            emissionFactorGroupId: $request->emissionFactorGroupId,
        );
    }

    /** @param array<EconomicSector> $economicSectors */
    private function latestYear(array $economicSectors, EmissionFactorGroupId $emissionFactorGroupId): ?int
    {
        $latest = null;

        foreach ($economicSectors as $economicSector) {
            foreach ($economicSector->emissionFactors() as $economicSectorEmissionFactor) {
                if (!$economicSectorEmissionFactor->emissionFactorGroupId()->equals($emissionFactorGroupId)) {
                    continue;
                }

                $year = $economicSectorEmissionFactor->year();

                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
