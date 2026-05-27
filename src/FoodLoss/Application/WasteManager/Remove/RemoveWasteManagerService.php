<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Remove;

use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveWasteManagerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private WasteManagerRepository $repository, private WasteManagerAuthorizer $authorizer) {}

    public function __invoke(RemoveWasteManagerRequest $request, User $user): RemoveWasteManagerResponse
    {
        $wasteManager = $this->repository->find($request->wasteManagerId);

        if (null == $wasteManager) {
            throw new \InvalidArgumentException('WasteManager not found');
        }

        $this->authorizer->denyUnlessCanDoThat($wasteManager, SecurityAction::Remove, $user);

        $wasteManager->remove();

        $this->repository->add($wasteManager);

        return new RemoveWasteManagerResponse(
            wasteManagerId: $wasteManager->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveWasteManagerRequest;
    }
}
