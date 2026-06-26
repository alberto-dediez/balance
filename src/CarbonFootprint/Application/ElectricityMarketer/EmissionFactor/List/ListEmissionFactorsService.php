<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\List;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private ElectricityMarketerRepository $repository,
        private ElectricityMarketerAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new ElectricityMarketer(id: ElectricityMarketerId::generate()), SecurityAction::List, $user);

        $electricityMarketers = $this->repository->findByFilter(new ElectricityMarketerFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($electricityMarketers) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            electricityMarketers: $electricityMarketers,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<ElectricityMarketer> $electricityMarketers */
    private function latestYear(array $electricityMarketers): ?int
    {
        $latest = null;

        foreach ($electricityMarketers as $electricityMarketer) {
            foreach ($electricityMarketer->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
