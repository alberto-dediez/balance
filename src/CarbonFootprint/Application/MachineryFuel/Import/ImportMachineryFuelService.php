<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Import;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportMachineryFuelService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MachineryFuelRepository $machineryFuelRepository,
        private MachineryFuelAuthorizer $authorizer,
    ) {}

    public function __invoke(ImportMachineryFuelRequest $request, User $user): ImportMachineryFuelResponse
    {
        $year = $this->resolveYear($request->year);

        $machineryFuel = $this->resolveMachineryFuel($request, $user);

        $machineryFuel->setEmissionFactor($this->buildEmissionFactor($request, $machineryFuel->unit(), $year));

        $this->machineryFuelRepository->add($machineryFuel);

        return new ImportMachineryFuelResponse(
            machineryFuelId: $machineryFuel->id(),
        );
    }

    private function resolveMachineryFuel(ImportMachineryFuelRequest $request, User $user): MachineryFuel
    {
        if (!empty($request->machineryFuel)) {
            $machineryFuel = $this->machineryFuelRepository->find(new MachineryFuelId($request->machineryFuel));

            if (null === $machineryFuel) {
                throw new \InvalidArgumentException('MachineryFuel not found');
            }

            $this->authorizer->denyUnlessCanDoThat($machineryFuel, SecurityAction::Edit, $user);

            return $machineryFuel;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A machinery fuel or a name is required');
        }

        $machineryFuel = $this->machineryFuelRepository->findOneByName($name);

        if (null !== $machineryFuel) {
            $this->authorizer->denyUnlessCanDoThat($machineryFuel, SecurityAction::Edit, $user);

            return $machineryFuel;
        }

        return $this->registerMachineryFuel($name, $request, $user);
    }

    private function registerMachineryFuel(string $name, ImportMachineryFuelRequest $request, User $user): MachineryFuel
    {
        $id = MachineryFuelId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MachineryFuel(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $machineryFuel = new MachineryFuel($id);
        $machineryFuel->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Liter,
            MachineryType::tryFrom((string) $request->machineryType) ?? MachineryType::Agricultural,
            $isDefaultLocale ? null : $locale,
        );

        return $machineryFuel;
    }

    private function buildEmissionFactor(ImportMachineryFuelRequest $request, Unit $unit, int $year): EmissionFactor
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
            unit: $unit,
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
        return $command instanceof ImportMachineryFuelRequest;
    }
}
