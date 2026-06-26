<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\EmissionFactor\List;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialFilter;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private MaterialRepository $repository,
        private MaterialAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Material(id: MaterialId::generate()), SecurityAction::List, $user);

        $materials = $this->repository->findByFilter(new MaterialFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($materials) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            materials: $materials,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<Material> $materials */
    private function latestYear(array $materials): ?int
    {
        $latest = null;

        foreach ($materials as $material) {
            foreach ($material->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
