<?php

namespace App\Waste\Application\Property\Update;

use InvalidArgumentException;
use App\Waste\Domain\Model\Property\PropertyRepository;

class UpdatePropertyService
{
    public function __construct(private PropertyRepository $repository)
    {

    }

    public function __invoke(UpdatePropertyRequest $request): UpdatePropertyResponse
    {
        $property = $this->repository->find($request->propertyId);

        if (null == $property) {
            throw new InvalidArgumentException("Property not found");
        }

        $property->update($request->name, $request->accountId);

        $this->repository->add($property);

        return new UpdatePropertyResponse(
            propertyId: $property->id(),
            name: $property->name(),
            accountId: $property->accountId()
        );
    }
}