<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\List;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private MerchandisingItemRepository $repository,
        private MerchandisingItemAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MerchandisingItem(id: MerchandisingItemId::generate()), SecurityAction::List, $user);

        $merchandisingItems = $this->repository->findByFilter(new MerchandisingItemFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($merchandisingItems) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            merchandisingItems: $merchandisingItems,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<MerchandisingItem> $merchandisingItems */
    private function latestYear(array $merchandisingItems): ?int
    {
        $latest = null;

        foreach ($merchandisingItems as $merchandisingItem) {
            foreach ($merchandisingItem->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
