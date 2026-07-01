<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Import;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
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
    ) {}

    public function __invoke(ImportElectricityMarketerRequest $request, User $user): ImportElectricityMarketerResponse
    {
        $year = $this->resolveYear($request->year);

        $electricityMarketer = $this->resolveElectricityMarketer($request, $user);

        $electricityMarketer->setEmissionFactor($this->buildEmissionFactor($request, $year));

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

        $electricityMarketer = $this->electricityMarketerRepository->findOneByName($name);

        if (null !== $electricityMarketer) {
            $this->authorizer->denyUnlessCanDoThat($electricityMarketer, SecurityAction::Edit, $user);

            return $electricityMarketer;
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

    private function buildEmissionFactor(ImportElectricityMarketerRequest $request, int $year): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);

        if (0.0 === (float) $co2eq) {
            // Temporary FASE 2 compatibility fallback: real EmissionFactorGroup resolution lands in FASE 3/7/8/9.
            $co2eq = Gwp::ar6()->co2eqFor($co2, $ch4, $n2o);
        }

        return new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2,
            ch4: $ch4,
            n2o: $n2o,
            year: $year,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
            unit: Unit::KWh,
            source: '' === trim((string) $request->source) ? null : $request->source,
        );
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
