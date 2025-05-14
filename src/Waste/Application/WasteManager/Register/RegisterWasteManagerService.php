<?php

namespace App\Waste\Application\WasteManager\Register;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class RegisterWasteManagerService
{
    public function __construct(private WasteManagerRepository $wasteManagerRepository, private WasteManagerAuthorizer $authorizer)
    {
    }

    public function __invoke(RegisterWasteManagerRequest $request, User $user): RegisterWasteManagerResponse
    {
        $id = WasteManagerId::generate();

        $this->authorizer->denyUnlessCanDoThat(new WasteManager(
            id: WasteManagerId::generate(),
            accountId: $request->accountId
        ), Action::Register, $user);

        $wasteManager = new WasteManager($id);
        $wasteManager->register(
            name: $request->name,
            isPublic: $request->isPublic,
            accountId: $request->accountId
        );

        $this->wasteManagerRepository->add($wasteManager);

        return new RegisterWasteManagerResponse(
            wasteManagerId: $wasteManager->id(), 
            name: $wasteManager->name(),
            isPublic: $wasteManager->isPublic(),
            accountId: $wasteManager->accountId()
        );
    }
}