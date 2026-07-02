<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Import;

use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportWasteService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private WasteRepository $wasteRepository,
        private WasteAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportWasteRequest $request, User $user): ImportWasteResponse
    {
        $year = $this->resolveYear($request->year);

        $waste = $this->resolveWaste($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $waste->setEmissionFactor($this->buildEmissionFactor($request, $waste->unit(), $year, $group));

        $this->wasteRepository->add($waste);

        return new ImportWasteResponse(
            wasteId: $waste->id(),
        );
    }

    private function resolveWaste(ImportWasteRequest $request, User $user): Waste
    {
        if (!empty($request->waste)) {
            $waste = $this->wasteRepository->find(new WasteId($request->waste));

            if (null === $waste) {
                throw new \InvalidArgumentException('Waste not found');
            }

            $this->authorizer->denyUnlessCanDoThat($waste, SecurityAction::Edit, $user);

            return $waste;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A waste or a name is required');
        }

        $waste = $this->wasteRepository->findOneByName($name);

        if (null !== $waste) {
            $this->authorizer->denyUnlessCanDoThat($waste, SecurityAction::Edit, $user);

            return $waste;
        }

        return $this->registerWaste($name, $request, $user);
    }

    private function registerWaste(string $name, ImportWasteRequest $request, User $user): Waste
    {
        $id = WasteId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Waste(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $waste = new Waste($id);
        $waste->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilogram,
            1.0,
            $isDefaultLocale ? null : $locale,
        );

        return $waste;
    }

    private function buildEmissionFactor(ImportWasteRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        if (0.0 !== (float) $co2eq) {
            return new EmissionFactor(
                co2eq: $co2eq,
                co2: $co2,
                ch4: $ch4,
                n2o: $n2o,
                year: $year,
                emissionFactorGroupId: $group->id(),
                unit: $unit,
                source: $source,
            );
        }

        return $group->buildEmissionFactor($co2, $ch4, $n2o, $year, $unit, $source);
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
        return $command instanceof ImportWasteRequest;
    }
}
