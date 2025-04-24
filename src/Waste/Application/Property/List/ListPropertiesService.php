<?php

namespace App\Waste\Application\Property\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Application\Property\PropertyAuthorizer;
use App\Waste\Domain\Model\Property\PropertyRepository;

class ListPropertiesService
{
    public function __construct(private PropertyRepository $repository, private PropertyAuthorizer $authorizer)
    {

    }

    public function __invoke(ListPropertiesRequest $request, User $user): ListPropertiesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Property(
            id: PropertyId::generate(),
            accountId: $request->accountId
        ), Action::List, $user);

        $properties = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListPropertiesResponse($properties, $totalResults);
    }
}