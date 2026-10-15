<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Update;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
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

class UpdateCapitalGoodService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CapitalGoodRepository $capitalGoodRepository,
        private CapitalGoodAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateCapitalGoodRequest $request, User $user): UpdateCapitalGoodResponse
    {
        $capitalGood = $this->capitalGoodRepository->find($request->capitalGoodId);

        if (null == $capitalGood) {
            throw new \InvalidArgumentException('CapitalGood not found');
        }

        $this->authorizer->denyUnlessCanDoThat($capitalGood, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $capitalGood->update(
            name: $request->name,
            category: $request->category,
            unit: $request->unit,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $capitalGood->unit();

        $capitalGood->syncEmissionFactors(
            array_values(array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    $unit,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            )),
        );

        $this->capitalGoodRepository->add($capitalGood);

        return new UpdateCapitalGoodResponse(
            capitalGoodId: $capitalGood->id(),
            name: $capitalGood->name(),
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
        return $command instanceof UpdateCapitalGoodRequest;
    }
}
