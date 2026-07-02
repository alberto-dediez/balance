<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\Update;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateEmissionFactorGroupService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private EmissionFactorGroupAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorGroupRequest $request, User $user): UpdateEmissionFactorGroupResponse
    {
        $emissionFactorGroup = $this->emissionFactorGroupRepository->find($request->emissionFactorGroupId);

        if (null == $emissionFactorGroup) {
            throw new \InvalidArgumentException('EmissionFactorGroup not found');
        }

        $this->authorizer->denyUnlessCanDoThat($emissionFactorGroup, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $emissionFactorGroup->update(
            name: $request->name,
            gwp: new Gwp(
                gwpCo2: $request->gwpCo2,
                gwpCh4: $request->gwpCh4,
                gwpN2O: $request->gwpN2O,
            ),
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->emissionFactorGroupRepository->add($emissionFactorGroup);

        return new UpdateEmissionFactorGroupResponse(
            emissionFactorGroupId: $emissionFactorGroup->id(),
            name: $emissionFactorGroup->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorGroupRequest;
    }
}
