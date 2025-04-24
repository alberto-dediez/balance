<?php

namespace App\Waste\Application\Waste\Remove;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\WasteRepository;

class RemoveWasteService
{
    public function __construct(private WasteRepository $repository, private WasteAuthorizer $authorizer)
    {

    }

    public function __invoke(RemoveWasteRequest $request, User $user): RemoveWasteResponse
    {
        $waste = $this->repository->find($request->wasteId);

        if (null == $waste) {
            throw new InvalidArgumentException("Waste not found");
        }

        $this->authorizer->denyUnlessCanDoThat($waste, Action::Remove, $user);

        $waste->remove();

        $this->repository->add($waste);

        return new RemoveWasteResponse(
            wasteId: $waste->id()
        );
    }
}