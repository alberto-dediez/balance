<?php

namespace App\Waste\Application\Property\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Property\PropertyAuthorizer;
use App\Waste\Domain\Model\Property\PropertyRepository;

class RemovePropertyService
{
    public function __construct(private PropertyRepository $repository, private PropertyAuthorizer $authorizer)
    {

    }

    public function __invoke(RemovePropertyRequest $request, User $user): RemovePropertyResponse
    {
        $property = $this->repository->find($request->propertyId);

        if (null == $property) {
            throw new InvalidArgumentException("Property not found");
        }

        $this->authorizer->denyUnlessCanDoThat($property, Action::Remove, $user);

        $property->remove();

        $this->repository->add($property);

        return new RemovePropertyResponse(
            propertyId: $property->id()
        );
    }
}