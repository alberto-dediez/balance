<?php

namespace App\Property\Application\Destination\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Property\Application\Destination\DestinationAuthorizer;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveDestinationService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private DestinationRepository $repository, private DestinationAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveDestinationRequest $request, User $user): RemoveDestinationResponse
    {
        $destination = $this->repository->find($request->destinationId);

        if (null == $destination) {
            throw new InvalidArgumentException("Destination not found");
        }

        $this->authorizer->denyUnlessCanDoThat($destination, Action::Remove, $user);

        $destination->remove();

        $this->repository->add($destination);

        return new RemoveDestinationResponse(
            destinationId: $destination->id()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveDestinationRequest;
    }
}