<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Register;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterElectricityMarketerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ElectricityMarketerRepository $electricityMarketerRepository,
        private ElectricityMarketerAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterElectricityMarketerRequest $request, User $user): RegisterElectricityMarketerResponse
    {
        $id = ElectricityMarketerId::generate();

        $this->authorizer->denyUnlessCanDoThat(new ElectricityMarketer(id: $id), SecurityAction::List, $user);

        $electricityMarketer = new ElectricityMarketer($id);
        $electricityMarketer->register($request->name);

        $this->electricityMarketerRepository->add($electricityMarketer);

        return new RegisterElectricityMarketerResponse(
            electricityMarketerId: $electricityMarketer->id(),
            name: $electricityMarketer->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterElectricityMarketerRequest;
    }
}
