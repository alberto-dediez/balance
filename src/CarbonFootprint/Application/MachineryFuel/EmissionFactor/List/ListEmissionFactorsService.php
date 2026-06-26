<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\List;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private MachineryFuelRepository $repository,
        private MachineryFuelAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MachineryFuel(id: MachineryFuelId::generate()), SecurityAction::List, $user);

        $machineryFuels = $this->repository->findByFilter(new MachineryFuelFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($machineryFuels) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            machineryFuels: $machineryFuels,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<MachineryFuel> $machineryFuels */
    private function latestYear(array $machineryFuels): ?int
    {
        $latest = null;

        foreach ($machineryFuels as $machineryFuel) {
            foreach ($machineryFuel->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
