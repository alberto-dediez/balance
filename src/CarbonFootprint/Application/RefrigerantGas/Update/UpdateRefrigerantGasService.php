<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Update;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateRefrigerantGasService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RefrigerantGasRepository $refrigerantGasRepository,
        private RefrigerantGasAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateRefrigerantGasRequest $request, User $user): UpdateRefrigerantGasResponse
    {
        $refrigerantGas = $this->refrigerantGasRepository->find($request->refrigerantGasId);

        if (null == $refrigerantGas) {
            throw new \InvalidArgumentException('RefrigerantGas not found');
        }

        $this->authorizer->denyUnlessCanDoThat($refrigerantGas, SecurityAction::Edit, $user);

        $refrigerantGas->update(name: $request->name);

        $refrigerantGas->syncEmissionFactors(
            array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    Unit::Kilogram,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            ),
        );

        $this->refrigerantGasRepository->add($refrigerantGas);

        return new UpdateRefrigerantGasResponse(
            refrigerantGasId: $refrigerantGas->id(),
            name: $refrigerantGas->name(),
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
        return $command instanceof UpdateRefrigerantGasRequest;
    }
}
