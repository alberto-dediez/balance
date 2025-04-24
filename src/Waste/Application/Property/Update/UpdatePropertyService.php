<?php

namespace App\Waste\Application\Property\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Property\PropertyAuthorizer;
use App\Waste\Domain\Model\Property\PropertyRepository;

class UpdatePropertyService
{
    public function __construct(private PropertyRepository $repository, private PropertyAuthorizer $authorizer)
    {

    }

    public function __invoke(UpdatePropertyRequest $request, User $user): UpdatePropertyResponse
    {
        $property = $this->repository->find($request->propertyId);

        if (null == $property) {
            throw new InvalidArgumentException("Property not found");
        }

        $this->authorizer->denyUnlessCanDoThat($property, Action::Edit, $user);

        $property->update($request->name, $request->accountId);

        $this->repository->add($property);

        return new UpdatePropertyResponse(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId()
        );
    }
}