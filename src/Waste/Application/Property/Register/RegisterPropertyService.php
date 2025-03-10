<?php

namespace App\Waste\Application\Property\Register;

use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyRepository;

class RegisterPropertyService
{
    public function __construct(private PropertyRepository $propertyRepository)
    {
    }

    public function __invoke(RegisterPropertyRequest $request): RegisterPropertyResponse
    {
        $id = PropertyId::generate();

        $property = new Property($id);
        $property->register($request->name, $request->account);

        $this->propertyRepository->add($property);

        return new RegisterPropertyResponse($property->id(), $property->name(), $property->account());
    }
}