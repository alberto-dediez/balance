<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Autocomplete;

use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationFilter;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class AutocompleteDestinationService
{
    public function __construct(private readonly DestinationRepository $repository, private readonly DestinationAuthorizer $authorizer) {}

    public function __invoke(AutocompleteDestinationRequest $request, User $user): AutocompleteDestinationResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Destination(
            id: DestinationId::generate(),
        ), Action::Autocomplete, $user);

        $filter = new DestinationFilter(
            name: $request->query,
        );

        $destinations = $this->repository->findByFilter($filter);

        return new AutocompleteDestinationResponse($destinations);
    }
}
