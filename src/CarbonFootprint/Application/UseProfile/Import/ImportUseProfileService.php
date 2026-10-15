<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Import;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportUseProfileService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UseProfileRepository $useProfileRepository,
        private UseProfileAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportUseProfileRequest $request, User $user): ImportUseProfileResponse
    {
        $year = $this->resolveYear($request->year);

        $useProfile = $this->resolveUseProfile($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $useProfile->setEmissionFactor($this->buildEmissionFactor($request, $useProfile->unit(), $year, $group));

        $this->useProfileRepository->add($useProfile);

        return new ImportUseProfileResponse(
            useProfileId: $useProfile->id(),
        );
    }

    private function resolveUseProfile(ImportUseProfileRequest $request, User $user): UseProfile
    {
        if (!empty($request->useProfile)) {
            $useProfile = $this->useProfileRepository->find(new UseProfileId($request->useProfile));

            if (null === $useProfile) {
                throw new \InvalidArgumentException('Use profile not found');
            }

            $this->authorizer->denyUnlessCanDoThat($useProfile, SecurityAction::Edit, $user);

            return $useProfile;
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A use profile or a name is required');
        }

        return $this->registerUseProfile($name, $request, $user);
    }

    private function registerUseProfile(string $name, ImportUseProfileRequest $request, User $user): UseProfile
    {
        $id = UseProfileId::generate();

        $this->authorizer->denyUnlessCanDoThat(new UseProfile(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $useProfile = new UseProfile($id);
        $useProfile->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Piece,
            $isDefaultLocale ? null : $locale,
        );

        return $useProfile;
    }

    private function buildEmissionFactor(ImportUseProfileRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportUseProfileRequest;
    }
}
