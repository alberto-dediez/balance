<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\EmissionFactor\List;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportFilter;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private TransportRepository $repository,
        private TransportAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Transport(id: TransportId::generate()), SecurityAction::List, $user);

        $transports = $this->repository->findByFilter(new TransportFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($transports, $request->emissionFactorGroupId) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            transports: $transports,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
            emissionFactorGroupId: $request->emissionFactorGroupId,
        );
    }

    /** @param array<Transport> $transports */
    private function latestYear(array $transports, EmissionFactorGroupId $emissionFactorGroupId): ?int
    {
        $latest = null;

        foreach ($transports as $transport) {
            foreach ($transport->emissionFactors() as $emissionFactor) {
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
