<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\EmissionFactor\List;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorFilter;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private GdOFactorRepository $repository,
        private GdOFactorAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new GdOFactor(id: GdOFactorId::generate()), SecurityAction::List, $user);

        $gdOFactors = $this->repository->findByFilter(new GdOFactorFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($gdOFactors) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            gdOFactors: $gdOFactors,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<GdOFactor> $gdOFactors */
    private function latestYear(array $gdOFactors): ?int
    {
        $latest = null;

        foreach ($gdOFactors as $gdOFactor) {
            foreach ($gdOFactor->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
