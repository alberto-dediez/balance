<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Update;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixCountryAlreadyRegisteredException;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateElectricityMixService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ElectricityMixRepository $electricityMixRepository,
        private ElectricityMixAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateElectricityMixRequest $request, User $user): UpdateElectricityMixResponse
    {
        $electricityMix = $this->electricityMixRepository->find($request->electricityMixId);

        if (null == $electricityMix) {
            throw new \InvalidArgumentException('ElectricityMix not found');
        }

        $this->authorizer->denyUnlessCanDoThat($electricityMix, SecurityAction::Edit, $user);

        $countryId = $request->countryId;

        if (null === $countryId) {
            throw new \InvalidArgumentException('A country is required');
        }

        $existing = $this->electricityMixRepository->findByCountryId($countryId);

        if (null !== $existing && (string) $existing->id() !== (string) $electricityMix->id()) {
            throw ElectricityMixCountryAlreadyRegisteredException::forCountry($countryId);
        }

        $electricityMix->update($countryId);

        $electricityMix->syncEmissionFactors(
            array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    Unit::KWh,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            ),
        );

        $this->electricityMixRepository->add($electricityMix);

        return new UpdateElectricityMixResponse(
            electricityMixId: $electricityMix->id(),
            countryId: $electricityMix->countryId(),
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
        return $command instanceof UpdateElectricityMixRequest;
    }
}
