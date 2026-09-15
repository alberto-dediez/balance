<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\List;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private ElectricityMixRepository $repository,
        private ElectricityMixAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ElectricityMix(id: ElectricityMixId::generate()), SecurityAction::List, $user);

        $electricityMixes = $this->repository->findByFilter(new ElectricityMixFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($electricityMixes, $request->emissionFactorGroupId) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            electricityMixes: $electricityMixes,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
            emissionFactorGroupId: $request->emissionFactorGroupId,
        );
    }

    /** @param array<ElectricityMix> $electricityMixes */
    private function latestYear(array $electricityMixes, EmissionFactorGroupId $emissionFactorGroupId): ?int
    {
        $latest = null;

        foreach ($electricityMixes as $electricityMix) {
            foreach ($electricityMix->emissionFactors() as $emissionFactor) {
                if (!$emissionFactor->emissionFactorGroupId()->equals($emissionFactorGroupId)) {
                    continue;
                }

                $year = $emissionFactor->year();

                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
