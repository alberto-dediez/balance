<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\Update;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateEmissionFactorsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ElectricityMarketerRepository $electricityMarketerRepository,
        private ElectricityMarketerAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $electricityMarketer = $this->electricityMarketerRepository->find($row->electricityMarketerId());

            if (null === $electricityMarketer) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($electricityMarketer, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $electricityMarketer->removeEmissionFactor($request->year);
            } else {
                $electricityMarketer->setEmissionFactor($row->toEmissionFactor($request->year, Unit::KWh));
            }

            $this->electricityMarketerRepository->add($electricityMarketer);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
