<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Update;

use App\CarbonFootprint\Application\EconomicSector\ClassificationCodeNormalizer;
use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorCodeAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateEconomicSectorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EconomicSectorRepository $economicSectorRepository,
        private EconomicSectorAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateEconomicSectorRequest $request, User $user): UpdateEconomicSectorResponse
    {
        $economicSector = $this->economicSectorRepository->find($request->economicSectorId);

        if (null == $economicSector) {
            throw new \InvalidArgumentException('EconomicSector not found');
        }

        $this->authorizer->denyUnlessCanDoThat($economicSector, SecurityAction::Edit, $user);

        $classificationCode = ClassificationCodeNormalizer::normalize($request->classificationCode);

        if (null !== $classificationCode) {
            $found = $this->economicSectorRepository->findByClassificationCode($classificationCode);

            if (null !== $found && !$found->id()->equals($economicSector->id())) {
                throw new EconomicSectorCodeAlreadyExistsException($classificationCode);
            }
        }

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $economicSector->update(
            name: $request->name,
            unit: $request->unit,
            classificationCode: $classificationCode,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $economicSector->unit();

        $economicSector->syncEmissionFactors(
            array_values(array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    $unit,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            )),
        );

        $this->economicSectorRepository->add($economicSector);

        return new UpdateEconomicSectorResponse(
            economicSectorId: $economicSector->id(),
            name: $economicSector->name(),
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
        return $command instanceof UpdateEconomicSectorRequest;
    }
}
