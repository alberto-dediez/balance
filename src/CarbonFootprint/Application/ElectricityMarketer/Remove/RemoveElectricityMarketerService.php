<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Remove;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveElectricityMarketerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private ElectricityMarketerRepository $repository, private ElectricityMarketerAuthorizer $authorizer) {}

    public function __invoke(RemoveElectricityMarketerRequest $request, User $user): RemoveElectricityMarketerResponse
    {
        $electricityMarketer = $this->repository->find($request->electricityMarketerId);

        if (null == $electricityMarketer) {
            throw new \InvalidArgumentException('ElectricityMarketer not found');
        }

        $this->authorizer->denyUnlessCanDoThat($electricityMarketer, SecurityAction::Remove, $user);

        $electricityMarketer->remove();

        $this->repository->add($electricityMarketer);

        return new RemoveElectricityMarketerResponse(
            electricityMarketerId: $electricityMarketer->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveElectricityMarketerRequest;
    }
}
