<?php

namespace App\Waste\Application\Property\Remove;

use App\Waste\Domain\Model\Property\PropertyRepository;
use InvalidArgumentException;

class RemovePropertyService
{
    public function __construct(private PropertyRepository $repository)
    {

    }

    public function __invoke(RemovePropertyRequest $request): RemovePropertyResponse
    {
        $property = $this->repository->find($request->propertyId);

        if (null == $property) {
            throw new InvalidArgumentException("Property not found");
        }

        $property->remove();

        $this->repository->add($property);

        return new RemovePropertyResponse(
            propertyId: $property->id()
        );
    }
}