<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Remove;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveElectricityMixService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private ElectricityMixRepository $repository, private ElectricityMixAuthorizer $authorizer) {}

    public function __invoke(RemoveElectricityMixRequest $request, User $user): RemoveElectricityMixResponse
    {
        $electricityMix = $this->repository->find($request->electricityMixId);

        if (null == $electricityMix) {
            throw new \InvalidArgumentException('ElectricityMix not found');
        }

        $this->authorizer->denyUnlessCanDoThat($electricityMix, SecurityAction::Remove, $user);

        $electricityMix->remove();

        $this->repository->add($electricityMix);

        return new RemoveElectricityMixResponse(
            electricityMixId: $electricityMix->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveElectricityMixRequest;
    }
}
