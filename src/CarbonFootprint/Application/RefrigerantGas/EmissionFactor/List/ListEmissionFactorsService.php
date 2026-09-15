<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\EmissionFactor\List;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasFilter;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private RefrigerantGasRepository $repository,
        private RefrigerantGasAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new RefrigerantGas(id: RefrigerantGasId::generate()), SecurityAction::List, $user);

        $refrigerantGases = $this->repository->findByFilter(new RefrigerantGasFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($refrigerantGases, $request->emissionFactorGroupId) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            refrigerantGases: $refrigerantGases,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
            emissionFactorGroupId: $request->emissionFactorGroupId,
        );
    }

    /** @param array<RefrigerantGas> $refrigerantGases */
    private function latestYear(array $refrigerantGases, EmissionFactorGroupId $emissionFactorGroupId): ?int
    {
        $latest = null;

        foreach ($refrigerantGases as $refrigerantGas) {
            foreach ($refrigerantGas->emissionFactors() as $emissionFactor) {
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
