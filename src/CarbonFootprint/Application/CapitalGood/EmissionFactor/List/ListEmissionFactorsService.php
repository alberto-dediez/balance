<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\EmissionFactor\List;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodFilter;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private CapitalGoodRepository $repository,
        private CapitalGoodAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CapitalGood(id: CapitalGoodId::generate()), SecurityAction::List, $user);

        $capitalGoods = $this->repository->findByFilter(new CapitalGoodFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($capitalGoods, $request->emissionFactorGroupId) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            capitalGoods: $capitalGoods,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
            emissionFactorGroupId: $request->emissionFactorGroupId,
        );
    }

    /** @param array<CapitalGood> $capitalGoods */
    private function latestYear(array $capitalGoods, EmissionFactorGroupId $emissionFactorGroupId): ?int
    {
        $latest = null;

        foreach ($capitalGoods as $capitalGood) {
            foreach ($capitalGood->emissionFactors() as $capitalGoodEmissionFactor) {
                if (!$capitalGoodEmissionFactor->emissionFactorGroupId()->equals($emissionFactorGroupId)) {
                    continue;
                }

                $year = $capitalGoodEmissionFactor->year();

                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
