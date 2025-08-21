<?php

namespace App\Waste\Application\WasteManager\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class UpdateWasteManagerService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(private WasteManagerRepository $repository, private WasteManagerAuthorizer $authorizer)
    {

    }

    public function __invoke(UpdateWasteManagerRequest $request, User $user): UpdateWasteManagerResponse
    {
        $wasteManager = $this->repository->find($request->wasteManagerId);

        if (null == $wasteManager) {
            throw new InvalidArgumentException("WasteManager not found");
        }

        $this->authorizer->denyUnlessCanDoThat($wasteManager, Action::Edit, $user);

        $wasteManager->update(
            name: $request->name, 
            isPublic: $request->isPublic
        );

        $this->repository->add($wasteManager);

        return new UpdateWasteManagerResponse(
            wasteManagerId: $wasteManager->id(),
            name: $wasteManager->name(),
            isPublic: $wasteManager->isPublic()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateWasteManagerRequest;
    }
}