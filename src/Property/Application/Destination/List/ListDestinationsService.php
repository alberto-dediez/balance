<?php

namespace App\Property\Application\Destination\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;

class ListDestinationsService
{
    public function __construct(private readonly DestinationRepository $repository, private readonly DestinationAuthorizer $authorizer)
    {

    }

    public function __invoke(ListDestinationsRequest $request, User $user): ListDestinationsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Destination(
            id: DestinationId::generate(),
            countryId: $request->countryId
        ), Action::List, $user);

        $destinations = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListDestinationsResponse($destinations, $totalResults);
    }
}