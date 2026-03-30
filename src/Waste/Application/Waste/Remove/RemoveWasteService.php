<?php

declare(strict_types=1);

namespace App\Waste\Application\Waste\Remove;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\WasteRepository;

class RemoveWasteService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private WasteRepository $repository, private WasteAuthorizer $authorizer) {}

    public function __invoke(RemoveWasteRequest $request, User $user): RemoveWasteResponse
    {
        $waste = $this->repository->find($request->wasteId);

        if (null == $waste) {
            throw new \InvalidArgumentException('Waste not found');
        }

        $this->authorizer->denyUnlessCanDoThat($waste, Action::Remove, $user);

        $waste->remove();

        $this->repository->add($waste);

        return new RemoveWasteResponse(
            wasteId: $waste->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveWasteRequest;
    }
}
