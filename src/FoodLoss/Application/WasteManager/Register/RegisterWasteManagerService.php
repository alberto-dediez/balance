<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Register;

use App\FoodLoss\Application\WasteManager\WasteManagerAuthorizer;
use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterWasteManagerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private WasteManagerRepository $wasteManagerRepository,
        private WasteManagerAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterWasteManagerRequest $request, User $user): RegisterWasteManagerResponse
    {
        $accountId = $request->accountId;

        if (!$accountId instanceof AccountId) {
            throw new \InvalidArgumentException('User must have an account');
        }

        $id = WasteManagerId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new WasteManager(
                id: $id,
                accountId: $accountId,
            ),
            SecurityAction::List,
            $user,
        );

        $wasteManager = new WasteManager($id);
        $wasteManager->register(
            name: $request->name,
            animalFeedAuthorized: $request->animalFeedAuthorized,
            compostAuthorized: $request->compostAuthorized,
            accountId: $accountId,
        );

        $this->wasteManagerRepository->add($wasteManager);

        return new RegisterWasteManagerResponse(
            wasteManagerId: $wasteManager->id(),
            name: $wasteManager->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterWasteManagerRequest;
    }
}
