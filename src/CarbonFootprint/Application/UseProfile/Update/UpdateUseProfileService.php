<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Update;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateUseProfileService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UseProfileRepository $useProfileRepository,
        private UseProfileAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateUseProfileRequest $request, User $user): UpdateUseProfileResponse
    {
        $useProfile = $this->useProfileRepository->find($request->useProfileId);

        if (null == $useProfile) {
            throw new \InvalidArgumentException('UseProfile not found');
        }

        $this->authorizer->denyUnlessCanDoThat($useProfile, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $useProfile->update(
            name: $request->name,
            unit: $request->unit,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $useProfile->unit();

        $useProfile->syncEmissionFactors(
            array_values(array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    $unit,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            )),
        );

        $this->useProfileRepository->add($useProfile);

        return new UpdateUseProfileResponse(
            useProfileId: $useProfile->id(),
            name: $useProfile->name(),
        );
    }

    private function resolveEmissionFactorGroup(?string $emissionFactorGroupId): EmissionFactorGroup
    {
        $emissionFactorGroup = null !== $emissionFactorGroupId
            ? $this->emissionFactorGroupRepository->find(new EmissionFactorGroupId($emissionFactorGroupId))
            : null;

        if (null === $emissionFactorGroup) {
            throw new \InvalidArgumentException('Emission factor group not found');
        }

        return $emissionFactorGroup;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateUseProfileRequest;
    }
}
