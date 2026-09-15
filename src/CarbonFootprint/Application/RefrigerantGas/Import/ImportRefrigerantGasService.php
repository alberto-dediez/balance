<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Import;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ImportRefrigerantGasService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RefrigerantGasRepository $refrigerantGasRepository,
        private RefrigerantGasAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportRefrigerantGasRequest $request, User $user): ImportRefrigerantGasResponse
    {
        $year = $this->resolveYear($request->year);

        $refrigerantGas = $this->resolveRefrigerantGas($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $refrigerantGas->setEmissionFactor($this->buildEmissionFactor($request, $year, $group));

        $this->refrigerantGasRepository->add($refrigerantGas);

        return new ImportRefrigerantGasResponse(
            refrigerantGasId: $refrigerantGas->id(),
        );
    }

    private function resolveRefrigerantGas(ImportRefrigerantGasRequest $request, User $user): RefrigerantGas
    {
        if (!empty($request->refrigerantGas)) {
            $refrigerantGas = $this->refrigerantGasRepository->find(new RefrigerantGasId($request->refrigerantGas));

            if (null === $refrigerantGas) {
                throw new \InvalidArgumentException('RefrigerantGas not found');
            }

            $this->authorizer->denyUnlessCanDoThat($refrigerantGas, SecurityAction::Edit, $user);

            return $refrigerantGas;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A refrigerant gas or a name is required');
        }

        return $this->registerRefrigerantGas($name, $user);
    }

    private function registerRefrigerantGas(string $name, User $user): RefrigerantGas
    {
        $id = RefrigerantGasId::generate();

        $this->authorizer->denyUnlessCanDoThat(new RefrigerantGas(id: $id), SecurityAction::List, $user);

        $refrigerantGas = new RefrigerantGas($id);
        $refrigerantGas->register($name);

        return $refrigerantGas;
    }

    private function buildEmissionFactor(ImportRefrigerantGasRequest $request, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, Unit::Kilogram, $source);
    }

    private function resolveEmissionFactorGroup(?string $value): EmissionFactorGroup
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('An emission factor group is required');
        }

        $group = $this->emissionFactorGroupRepository->find(new EmissionFactorGroupId($value));

        if (null === $group) {
            throw new \InvalidArgumentException('Emission factor group not found');
        }

        return $group;
    }

    private function resolveYear(?string $year): int
    {
        $parsed = (int) $year;

        if ($parsed <= 0) {
            throw new \InvalidArgumentException('A valid year is required');
        }

        return $parsed;
    }

    private function decimal(?string $value): string
    {
        $value = trim((string) $value);

        return '' === $value ? '0' : str_replace(',', '.', $value);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ImportRefrigerantGasRequest;
    }
}
