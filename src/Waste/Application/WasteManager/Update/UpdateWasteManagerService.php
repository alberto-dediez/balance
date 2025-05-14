<?php

namespace App\Waste\Application\WasteManager\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class UpdateWasteManagerService
{
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
}