<?php

namespace App\Waste\Application\Property\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Application\Property\PropertyAuthorizer;
use App\Waste\Domain\Model\Property\PropertyRepository;

class RegisterPropertyService
{
    public function __construct(private PropertyRepository $propertyRepository, private PropertyAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterPropertyRequest $request, User $user): RegisterPropertyResponse
    {
        $id = PropertyId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Property(
            id: PropertyId::generate(),
            accountId: $request->accountId
        ), Action::Register, $user);

        $property = new Property($id);
        $property->register($request->name, $request->accountId);

        $this->propertyRepository->add($property);

        return new RegisterPropertyResponse($property->id(), $property->name(), $property->accountId());
    }
}