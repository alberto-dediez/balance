<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Packaging\Remove;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemovePackagingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private PackagingRepository $repository, private PackagingAuthorizer $authorizer) {}

    public function __invoke(RemovePackagingRequest $request, User $user): RemovePackagingResponse
    {
        $packaging = $this->repository->find($request->packagingId);

        if (null == $packaging) {
            throw new \InvalidArgumentException('Packaging not found');
        }

        $this->authorizer->denyUnlessCanDoThat($packaging, SecurityAction::Remove, $user);

        $packaging->remove();

        $this->repository->add($packaging);

        return new RemovePackagingResponse(
            packagingId: $packaging->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemovePackagingRequest;
    }
}
