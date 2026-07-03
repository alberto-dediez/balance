<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Import;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
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
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportCombustibleService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CombustibleRepository $combustibleRepository,
        private CombustibleAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportCombustibleRequest $request, User $user): ImportCombustibleResponse
    {
        $year = $this->resolveYear($request->year);

        $combustible = $this->resolveCombustible($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $combustible->setEmissionFactor($this->buildEmissionFactor($request, $combustible->unit(), $year, $group));

        $this->combustibleRepository->add($combustible);

        return new ImportCombustibleResponse(
            combustibleId: $combustible->id(),
        );
    }

    private function resolveCombustible(ImportCombustibleRequest $request, User $user): Combustible
    {
        if (!empty($request->combustible)) {
            $combustible = $this->combustibleRepository->find(new CombustibleId($request->combustible));

            if (null === $combustible) {
                throw new \InvalidArgumentException('Combustible not found');
            }

            $this->authorizer->denyUnlessCanDoThat($combustible, SecurityAction::Edit, $user);

            return $combustible;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A combustible or a name is required');
        }

        return $this->registerCombustible($name, $request, $user);
    }

    private function registerCombustible(string $name, ImportCombustibleRequest $request, User $user): Combustible
    {
        $id = CombustibleId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Combustible(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $combustible = new Combustible($id);
        $combustible->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilogram,
            $isDefaultLocale ? null : $locale,
        );

        return $combustible;
    }

    private function buildEmissionFactor(ImportCombustibleRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, $unit, $source);
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
        return $command instanceof ImportCombustibleRequest;
    }
}
