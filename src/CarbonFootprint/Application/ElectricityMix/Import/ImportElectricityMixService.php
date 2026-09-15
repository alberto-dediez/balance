<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Import;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ImportElectricityMixService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ElectricityMixRepository $electricityMixRepository,
        private ElectricityMixAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private CountryRepository $countryRepository,
    ) {}

    public function __invoke(ImportElectricityMixRequest $request, User $user): ImportElectricityMixResponse
    {
        $year = $this->resolveYear($request->year);

        $electricityMix = $this->resolveElectricityMix($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $electricityMix->setEmissionFactor($this->buildEmissionFactor($request, $year, $group));

        $this->electricityMixRepository->add($electricityMix);

        return new ImportElectricityMixResponse(
            electricityMixId: $electricityMix->id(),
        );
    }

    private function resolveElectricityMix(ImportElectricityMixRequest $request, User $user): ElectricityMix
    {
        if (!empty($request->electricityMix)) {
            $electricityMix = $this->electricityMixRepository->find(new ElectricityMixId($request->electricityMix));

            if (null === $electricityMix) {
                throw new \InvalidArgumentException('ElectricityMix not found');
            }

            $this->authorizer->denyUnlessCanDoThat($electricityMix, SecurityAction::Edit, $user);

            return $electricityMix;
        }

        $countryId = $this->resolveCountryId($request->country);

        $electricityMix = $this->electricityMixRepository->findByCountryId($countryId);

        if (null !== $electricityMix) {
            $this->authorizer->denyUnlessCanDoThat($electricityMix, SecurityAction::Edit, $user);

            return $electricityMix;
        }

        return $this->registerElectricityMix($countryId, $user);
    }

    private function resolveCountryId(?string $iso): CountryId
    {
        $iso = trim((string) $iso);

        if (empty($iso)) {
            throw new \InvalidArgumentException('A country is required');
        }

        $country = $this->countryRepository->findByIso($iso);

        if (null === $country) {
            throw new \InvalidArgumentException('Country not found');
        }

        return $country->id();
    }

    private function registerElectricityMix(CountryId $countryId, User $user): ElectricityMix
    {
        $id = ElectricityMixId::generate();

        $this->authorizer->denyUnlessCanDoThat(new ElectricityMix(id: $id), SecurityAction::List, $user);

        $electricityMix = new ElectricityMix($id);
        $electricityMix->register($countryId);

        return $electricityMix;
    }

    private function buildEmissionFactor(ImportElectricityMixRequest $request, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportElectricityMixRequest;
    }
}
