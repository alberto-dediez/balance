<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Update;

use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateWasteManagerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private WasteManagerRepository $wasteManagerRepository,
        private WasteManagerAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateWasteManagerRequest $request, User $user): UpdateWasteManagerResponse
    {
        $wasteManager = $this->wasteManagerRepository->find($request->wasteManagerId);

        if (null == $wasteManager) {
            throw new \InvalidArgumentException('WasteManager not found');
        }

        $this->authorizer->denyUnlessCanDoThat($wasteManager, SecurityAction::Edit, $user);

        $wasteManager->update(
            name: $request->name,
            animalFeedAuthorized: $request->animalFeedAuthorized,
            compostAuthorized: $request->compostAuthorized,
        );

        $this->wasteManagerRepository->add($wasteManager);

        return new UpdateWasteManagerResponse(
            wasteManagerId: $wasteManager->id(),
            name: $wasteManager->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateWasteManagerRequest;
    }
}
