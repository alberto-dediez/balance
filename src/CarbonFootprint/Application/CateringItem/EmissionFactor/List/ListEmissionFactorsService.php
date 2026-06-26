<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\EmissionFactor\List;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private CateringItemRepository $repository,
        private CateringItemAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CateringItem(id: CateringItemId::generate()), SecurityAction::List, $user);

        $cateringItems = $this->repository->findByFilter(new CateringItemFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($cateringItems) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            cateringItems: $cateringItems,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<CateringItem> $cateringItems */
    private function latestYear(array $cateringItems): ?int
    {
        $latest = null;

        foreach ($cateringItems as $cateringItem) {
            foreach ($cateringItem->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
