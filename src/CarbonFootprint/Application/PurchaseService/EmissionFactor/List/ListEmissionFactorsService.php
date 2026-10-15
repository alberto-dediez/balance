<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\EmissionFactor\List;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private PurchaseServiceRepository $repository,
        private PurchaseServiceAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new PurchaseService(id: PurchaseServiceId::generate()), SecurityAction::List, $user);

        $purchaseServices = $this->repository->findByFilter(new PurchaseServiceFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($purchaseServices, $request->emissionFactorGroupId) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            purchaseServices: $purchaseServices,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
            emissionFactorGroupId: $request->emissionFactorGroupId,
        );
    }

    /** @param array<PurchaseService> $purchaseServices */
    private function latestYear(array $purchaseServices, EmissionFactorGroupId $emissionFactorGroupId): ?int
    {
        $latest = null;

        foreach ($purchaseServices as $purchaseService) {
            foreach ($purchaseService->emissionFactors() as $purchaseServiceEmissionFactor) {
                if (!$purchaseServiceEmissionFactor->emissionFactorGroupId()->equals($emissionFactorGroupId)) {
                    continue;
                }

                $year = $purchaseServiceEmissionFactor->year();

                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
