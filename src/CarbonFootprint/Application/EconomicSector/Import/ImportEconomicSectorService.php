<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Import;

use App\CarbonFootprint\Application\EconomicSector\ClassificationCodeNormalizer;
use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
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

class ImportEconomicSectorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EconomicSectorRepository $economicSectorRepository,
        private EconomicSectorAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportEconomicSectorRequest $request, User $user): ImportEconomicSectorResponse
    {
        $year = $this->resolveYear($request->year);

        $economicSector = $this->resolveEconomicSector($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $economicSector->setEmissionFactor($this->buildEmissionFactor($request, $economicSector->unit(), $year, $group));

        $this->economicSectorRepository->add($economicSector);

        return new ImportEconomicSectorResponse(
            economicSectorId: $economicSector->id(),
        );
    }

    private function resolveEconomicSector(ImportEconomicSectorRequest $request, User $user): EconomicSector
    {
        if (!empty($request->economicSector)) {
            $economicSector = $this->economicSectorRepository->find(new EconomicSectorId($request->economicSector));

            if (null === $economicSector) {
                throw new \InvalidArgumentException('Economic sector not found');
            }

            $this->authorizer->denyUnlessCanDoThat($economicSector, SecurityAction::Edit, $user);

            return $economicSector;
        }

        $classificationCode = ClassificationCodeNormalizer::normalize($request->classificationCode);

        if (null !== $classificationCode) {
            $economicSector = $this->economicSectorRepository->findByClassificationCode($classificationCode);

            if (null !== $economicSector) {
                $this->authorizer->denyUnlessCanDoThat($economicSector, SecurityAction::Edit, $user);

                return $economicSector;
            }
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('An economic sector, a classification code or a name is required');
        }

        return $this->registerEconomicSector($name, $classificationCode, $request, $user);
    }

    private function registerEconomicSector(string $name, ?string $classificationCode, ImportEconomicSectorRequest $request, User $user): EconomicSector
    {
        $id = EconomicSectorId::generate();

        $this->authorizer->denyUnlessCanDoThat(new EconomicSector(id: $id), SecurityAction::List, $user);

        $unit = Unit::tryFrom((string) $request->unit) ?? Unit::Euro;

        if (!$unit->isCurrency()) {
            throw new \InvalidArgumentException('Unit must be a currency');
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $economicSector = new EconomicSector($id);
        $economicSector->register(
            $name,
            $unit,
            $classificationCode,
            $isDefaultLocale ? null : $locale,
        );

        return $economicSector;
    }

    private function buildEmissionFactor(ImportEconomicSectorRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportEconomicSectorRequest;
    }
}
