<?php

declare(strict_types=1);

namespace App\Waste\Application\WasteManager\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class RegisterWasteManagerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private WasteManagerRepository $wasteManagerRepository, private WasteManagerAuthorizer $authorizer) {}

    public function __invoke(RegisterWasteManagerRequest $request, User $user): RegisterWasteManagerResponse
    {
        $id = WasteManagerId::generate();

        $this->authorizer->denyUnlessCanDoThat(new WasteManager(
            id: WasteManagerId::generate(),
            accountId: $request->accountId,
        ), Action::Register, $user);

        assert($request->accountId instanceof AccountId);

        $wasteManager = new WasteManager($id);
        $wasteManager->register(
            name: $request->name,
            isPublic: $request->isPublic,
            accountId: $request->accountId,
        );

        $this->wasteManagerRepository->add($wasteManager);

        assert(null !== $wasteManager->name());
        assert($wasteManager->accountId() instanceof AccountId);

        return new RegisterWasteManagerResponse(
            wasteManagerId: $wasteManager->id(),
            name: $wasteManager->name(),
            isPublic: $wasteManager->isPublic(),
            accountId: $wasteManager->accountId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterWasteManagerRequest;
    }
}
