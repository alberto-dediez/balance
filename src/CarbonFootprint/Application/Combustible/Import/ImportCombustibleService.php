<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Import;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
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
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportCombustibleService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CombustibleRepository $combustibleRepository,
        private CombustibleAuthorizer $authorizer,
    ) {}

    public function __invoke(ImportCombustibleRequest $request, User $user): ImportCombustibleResponse
    {
        $year = $this->resolveYear($request->year);

        $combustible = $this->resolveCombustible($request, $user);

        $combustible->setEmissionFactor($this->buildEmissionFactor($request, $combustible->unit(), $year));

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

        $combustible = $this->combustibleRepository->findOneByName($name);

        if (null !== $combustible) {
            $this->authorizer->denyUnlessCanDoThat($combustible, SecurityAction::Edit, $user);

            return $combustible;
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

    private function buildEmissionFactor(ImportCombustibleRequest $request, Unit $unit, int $year): EmissionFactor
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
        return $command instanceof ImportCombustibleRequest;
    }
}
