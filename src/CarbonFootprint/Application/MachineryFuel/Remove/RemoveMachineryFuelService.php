<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Remove;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMachineryFuelService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private MachineryFuelRepository $repository, private MachineryFuelAuthorizer $authorizer) {}

    public function __invoke(RemoveMachineryFuelRequest $request, User $user): RemoveMachineryFuelResponse
    {
        $machineryFuel = $this->repository->find($request->machineryFuelId);

        if (null == $machineryFuel) {
            throw new \InvalidArgumentException('MachineryFuel not found');
        }

        $this->authorizer->denyUnlessCanDoThat($machineryFuel, SecurityAction::Remove, $user);

        $machineryFuel->remove();

        $this->repository->add($machineryFuel);

        return new RemoveMachineryFuelResponse(
            machineryFuelId: $machineryFuel->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMachineryFuelRequest;
    }
}
