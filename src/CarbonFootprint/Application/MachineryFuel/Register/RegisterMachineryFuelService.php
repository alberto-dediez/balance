<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Register;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMachineryFuelService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MachineryFuelRepository $machineryFuelRepository,
        private MachineryFuelAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMachineryFuelRequest $request, User $user): RegisterMachineryFuelResponse
    {
        $id = MachineryFuelId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MachineryFuel(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $machineryFuel = new MachineryFuel($id);
        $machineryFuel->register(
            $request->name,
            $request->unit,
            $request->machineryType,
            $isDefaultLocale ? null : $locale,
        );

        $this->machineryFuelRepository->add($machineryFuel);

        return new RegisterMachineryFuelResponse(
            machineryFuelId: $machineryFuel->id(),
            name: $machineryFuel->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMachineryFuelRequest;
    }
}
