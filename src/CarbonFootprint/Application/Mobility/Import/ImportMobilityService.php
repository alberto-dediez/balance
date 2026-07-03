<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Import;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportMobilityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MobilityRepository $mobilityRepository,
        private MobilityAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportMobilityRequest $request, User $user): ImportMobilityResponse
    {
        $year = $this->resolveYear($request->year);

        $mobility = $this->resolveMobility($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $mobility->setEmissionFactor($this->buildEmissionFactor($request, $mobility->unit(), $year, $group));

        $this->mobilityRepository->add($mobility);

        return new ImportMobilityResponse(
            mobilityId: $mobility->id(),
        );
    }

    private function resolveMobility(ImportMobilityRequest $request, User $user): Mobility
    {
        if (!empty($request->mobility)) {
            $mobility = $this->mobilityRepository->find(new MobilityId($request->mobility));

            if (null === $mobility) {
                throw new \InvalidArgumentException('Mobility not found');
            }

            $this->authorizer->denyUnlessCanDoThat($mobility, SecurityAction::Edit, $user);

            return $mobility;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A mobility or a name is required');
        }

        return $this->registerMobility($name, $request, $user);
    }

    private function registerMobility(string $name, ImportMobilityRequest $request, User $user): Mobility
    {
        $id = MobilityId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Mobility(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $mobility = new Mobility($id);
        $mobility->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilometer,
            $isDefaultLocale ? null : $locale,
        );

        return $mobility;
    }

    private function buildEmissionFactor(ImportMobilityRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportMobilityRequest;
    }
}
