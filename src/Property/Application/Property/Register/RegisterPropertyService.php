<?php

namespace App\Property\Application\Property\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RegisterPropertyService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

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
        $property->register(
            name: $request->name, 
            accountId: $request->accountId, 
            info: $request->info, 
            externalId: $request->externalId
        );

        $this->propertyRepository->add($property);

        return new RegisterPropertyResponse(
            propertyId: $property->id(), 
            name: $property->name(), 
            accountId: $property->accountId(),
            info: $property->info(),
            externalId: $property->externalId()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPropertyRequest;
    }
}