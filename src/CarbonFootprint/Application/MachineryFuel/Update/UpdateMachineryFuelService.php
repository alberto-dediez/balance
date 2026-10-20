<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Update;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMachineryFuelService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MachineryFuelRepository $machineryFuelRepository,
        private MachineryFuelAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateMachineryFuelRequest $request, User $user): UpdateMachineryFuelResponse
    {
        $machineryFuel = $this->machineryFuelRepository->find($request->machineryFuelId);

        if (null == $machineryFuel) {
            throw new \InvalidArgumentException('MachineryFuel not found');
        }

        $this->authorizer->denyUnlessCanDoThat($machineryFuel, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $machineryFuel->update(
            name: $request->name,
            unit: $request->unit,
            machineryType: $request->machineryType,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $machineryFuel->unit();

        $machineryFuel->syncEmissionFactors(
            array_values(array_map(
                function (EmissionFactorData $data) use ($unit): array {
                    $emissionFactorGroup = $this->resolveEmissionFactorGroup($data->emissionFactorGroupId);

                    return [
                        'factor' => $data->toEmissionFactor($unit, $emissionFactorGroup),
                        'wellToTank' => $data->wellToTankEmissionFactor($unit, $emissionFactorGroup),
                    ];
                },
                $request->emissionFactors,
            )),
        );

        $this->machineryFuelRepository->add($machineryFuel);

        return new UpdateMachineryFuelResponse(
            machineryFuelId: $machineryFuel->id(),
            name: $machineryFuel->name(),
        );
    }

    private function resolveEmissionFactorGroup(?string $emissionFactorGroupId): EmissionFactorGroup
    {
        $emissionFactorGroup = null !== $emissionFactorGroupId
            ? $this->emissionFactorGroupRepository->find(new EmissionFactorGroupId($emissionFactorGroupId))
            : null;

        if (null === $emissionFactorGroup) {
            throw new \InvalidArgumentException('Emission factor group not found');
        }

        return $emissionFactorGroup;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMachineryFuelRequest;
    }
}
