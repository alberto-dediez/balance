<?php

namespace App\Property\Application\Destination\Autocomplete;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;

class AutocompleteDestinationService
{
    public function __construct(private DestinationRepository $repository, private DestinationAuthorizer $authorizer)
    {

    }

    public function __invoke(AutocompleteDestinationRequest $request, User $user): AutocompleteDestinationResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Destination(
            id: DestinationId::generate()
        ), Action::Autocomplete, $user);

        $filter = new DestinationFilter(
            name: $request->query
        );

        $destinations = $this->repository->findByFilter($filter);

        return new AutocompleteDestinationResponse($destinations);
    }
}