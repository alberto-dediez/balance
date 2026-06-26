<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\List;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListRoadTransportsService
{
    public function __construct(private RoadTransportRepository $repository, private RoadTransportAuthorizer $authorizer) {}

    public function __invoke(ListRoadTransportsRequest $request, User $user): ListRoadTransportsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new RoadTransport(id: RoadTransportId::generate()), SecurityAction::List, $user);

        $roadTransports = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListRoadTransportsResponse($roadTransports, $totalResults);
    }
}
