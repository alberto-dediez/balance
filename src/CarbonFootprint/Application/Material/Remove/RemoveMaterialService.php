<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Remove;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMaterialService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private MaterialRepository $repository, private MaterialAuthorizer $authorizer) {}

    public function __invoke(RemoveMaterialRequest $request, User $user): RemoveMaterialResponse
    {
        $material = $this->repository->find($request->materialId);

        if (null == $material) {
            throw new \InvalidArgumentException('Material not found');
        }

        $this->authorizer->denyUnlessCanDoThat($material, SecurityAction::Remove, $user);

        $material->remove();

        $this->repository->add($material);

        return new RemoveMaterialResponse(
            materialId: $material->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMaterialRequest;
    }
}
