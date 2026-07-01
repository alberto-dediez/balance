<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Update;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMobilityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MobilityRepository $mobilityRepository,
        private MobilityAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateMobilityRequest $request, User $user): UpdateMobilityResponse
    {
        $mobility = $this->mobilityRepository->find($request->mobilityId);

        if (null == $mobility) {
            throw new \InvalidArgumentException('Mobility not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mobility, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $mobility->update(
            name: $request->name,
            unit: $request->unit,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $mobility->unit();

        $mobility->syncEmissionFactors(
            array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    $unit,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            ),
        );

        $this->mobilityRepository->add($mobility);

        return new UpdateMobilityResponse(
            mobilityId: $mobility->id(),
            name: $mobility->name(),
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
        return $command instanceof UpdateMobilityRequest;
    }
}
