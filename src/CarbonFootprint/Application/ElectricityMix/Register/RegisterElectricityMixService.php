<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Register;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixCountryAlreadyRegisteredException;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterElectricityMixService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ElectricityMixRepository $electricityMixRepository,
        private ElectricityMixAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterElectricityMixRequest $request, User $user): RegisterElectricityMixResponse
    {
        $id = ElectricityMixId::generate();

        $this->authorizer->denyUnlessCanDoThat(new ElectricityMix(id: $id), SecurityAction::List, $user);

        $countryId = $request->countryId;

        if (null === $countryId) {
            throw new \InvalidArgumentException('A country is required');
        }

        if (null !== $this->electricityMixRepository->findByCountryId($countryId)) {
            throw ElectricityMixCountryAlreadyRegisteredException::forCountry($countryId);
        }

        $electricityMix = new ElectricityMix($id);
        $electricityMix->register($countryId);

        $this->electricityMixRepository->add($electricityMix);

        return new RegisterElectricityMixResponse(
            electricityMixId: $electricityMix->id(),
            countryId: $electricityMix->countryId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterElectricityMixRequest;
    }
}
