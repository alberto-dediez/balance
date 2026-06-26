<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Update;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateElectricityMarketerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ElectricityMarketerRepository $electricityMarketerRepository,
        private ElectricityMarketerAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateElectricityMarketerRequest $request, User $user): UpdateElectricityMarketerResponse
    {
        $electricityMarketer = $this->electricityMarketerRepository->find($request->electricityMarketerId);

        if (null == $electricityMarketer) {
            throw new \InvalidArgumentException('ElectricityMarketer not found');
        }

        $this->authorizer->denyUnlessCanDoThat($electricityMarketer, SecurityAction::Edit, $user);

        $electricityMarketer->update(name: $request->name);

        $electricityMarketer->syncEmissionFactors(
            array_map(
                static fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(Unit::KWh),
                $request->emissionFactors,
            ),
        );

        $this->electricityMarketerRepository->add($electricityMarketer);

        return new UpdateElectricityMarketerResponse(
            electricityMarketerId: $electricityMarketer->id(),
            name: $electricityMarketer->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateElectricityMarketerRequest;
    }
}
