<?php

namespace App\Waste\Application\WasteManager\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\WasteManager\WasteManagerAuthorizer;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class RemoveWasteManagerService
{
    public function __construct(private WasteManagerRepository $repository, private WasteManagerAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveWasteManagerRequest $request, User $user): RemoveWasteManagerResponse
    {
        $wasteManager = $this->repository->find($request->wasteManagerId);

        if (null == $wasteManager) {
            throw new InvalidArgumentException("WasteManager not found");
        }

        $this->authorizer->denyUnlessCanDoThat($wasteManager, Action::Remove, $user);

        $wasteManager->remove();

        $this->repository->add($wasteManager);

        return new RemoveWasteManagerResponse(
            wasteManagerId: $wasteManager->id()
        );
    }
}