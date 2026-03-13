<?php

namespace App\FoodLoss\Application\Action\Remove;

use InvalidArgumentException;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class RemoveActionService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private ActionRepository $repository, private ActionAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveActionRequest $request, User $user): RemoveActionResponse
    {
        $action = $this->repository->find($request->actionId);

        if (null == $action) {
            throw new InvalidArgumentException("Action not found");
        }

        $this->authorizer->denyUnlessCanDoThat($action, SecurityAction::Remove, $user);

        $action->remove();

        $this->repository->add($action);

        return new RemoveActionResponse(
            actionId: $action->id()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveActionRequest;
    }
}