<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\EmissionFactor\List;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private OvernightStayRepository $repository,
        private OvernightStayAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new OvernightStay(id: OvernightStayId::generate()), SecurityAction::List, $user);

        $overnightStays = $this->repository->findByFilter(new OvernightStayFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($overnightStays) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            overnightStays: $overnightStays,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<OvernightStay> $overnightStays */
    private function latestYear(array $overnightStays): ?int
    {
        $latest = null;

        foreach ($overnightStays as $overnightStay) {
            foreach ($overnightStay->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
