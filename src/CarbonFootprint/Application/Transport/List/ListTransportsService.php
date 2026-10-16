<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\List;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListTransportsService
{
    public function __construct(private TransportRepository $repository, private TransportAuthorizer $authorizer) {}

    public function __invoke(ListTransportsRequest $request, User $user): ListTransportsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Transport(id: TransportId::generate()), SecurityAction::List, $user);

        $transports = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListTransportsResponse($transports, $totalResults);
    }
}
