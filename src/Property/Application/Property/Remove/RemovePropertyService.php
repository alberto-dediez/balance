<?php

namespace App\Property\Application\Property\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemovePropertyService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

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

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemovePropertyRequest;
    }
}