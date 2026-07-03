<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Import;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
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

class ImportElectricityMarketerService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ElectricityMarketerRepository $electricityMarketerRepository,
        private ElectricityMarketerAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportElectricityMarketerRequest $request, User $user): ImportElectricityMarketerResponse
    {
        $year = $this->resolveYear($request->year);

        $electricityMarketer = $this->resolveElectricityMarketer($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $electricityMarketer->setEmissionFactor($this->buildEmissionFactor($request, $year, $group));

        $this->electricityMarketerRepository->add($electricityMarketer);

        return new ImportElectricityMarketerResponse(
            electricityMarketerId: $electricityMarketer->id(),
        );
    }

    private function resolveElectricityMarketer(ImportElectricityMarketerRequest $request, User $user): ElectricityMarketer
    {
        if (!empty($request->electricityMarketer)) {
            $electricityMarketer = $this->electricityMarketerRepository->find(new ElectricityMarketerId($request->electricityMarketer));

            if (null === $electricityMarketer) {
                throw new \InvalidArgumentException('ElectricityMarketer not found');
            }

            $this->authorizer->denyUnlessCanDoThat($electricityMarketer, SecurityAction::Edit, $user);

            return $electricityMarketer;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('An electricity marketer or a name is required');
        }

        return $this->registerElectricityMarketer($name, $user);
    }

    private function registerElectricityMarketer(string $name, User $user): ElectricityMarketer
    {
        $id = ElectricityMarketerId::generate();

        $this->authorizer->denyUnlessCanDoThat(new ElectricityMarketer(id: $id), SecurityAction::List, $user);

        $electricityMarketer = new ElectricityMarketer($id);
        $electricityMarketer->register($name);

        return $electricityMarketer;
    }

    private function buildEmissionFactor(ImportElectricityMarketerRequest $request, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, Unit::KWh, $source);
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
        return $command instanceof ImportElectricityMarketerRequest;
    }
}
