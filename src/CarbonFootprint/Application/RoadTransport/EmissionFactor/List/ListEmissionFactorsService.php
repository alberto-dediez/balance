<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\EmissionFactor\List;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListEmissionFactorsService
{
    public function __construct(
        private RoadTransportRepository $repository,
        private RoadTransportAuthorizer $authorizer,
    ) {}

    public function __invoke(ListEmissionFactorsRequest $request, User $user): ListEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new RoadTransport(id: RoadTransportId::generate()), SecurityAction::List, $user);

        $roadTransports = $this->repository->findByFilter(new RoadTransportFilter(limit: 0));

        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $year = $request->year ?? $this->latestYear($roadTransports) ?? $currentYear;

        return new ListEmissionFactorsResponse(
            roadTransports: $roadTransports,
            year: $year,
            currentYear: $currentYear,
            canGoNext: $year < $currentYear,
        );
    }

    /** @param array<RoadTransport> $roadTransports */
    private function latestYear(array $roadTransports): ?int
    {
        $latest = null;

        foreach ($roadTransports as $roadTransport) {
            foreach ($roadTransport->emissionFactors()->years() as $year) {
                if (null === $latest || $year > $latest) {
                    $latest = $year;
                }
            }
        }

        return $latest;
    }
}
