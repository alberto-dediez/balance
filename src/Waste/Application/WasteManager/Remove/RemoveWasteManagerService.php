<?php

declare(strict_types=1);

namespace App\Waste\Application\WasteManager\Remove;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

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

        $this->authorizer->denyUnlessCanDoThat($wasteManager, Action::Remove, $user);

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
