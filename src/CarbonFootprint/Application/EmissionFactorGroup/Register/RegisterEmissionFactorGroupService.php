<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\Register;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterEmissionFactorGroupService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private EmissionFactorGroupAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterEmissionFactorGroupRequest $request, User $user): RegisterEmissionFactorGroupResponse
    {
        $id = EmissionFactorGroupId::generate();

        $this->authorizer->denyUnlessCanDoThat(new EmissionFactorGroup(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $emissionFactorGroup = new EmissionFactorGroup($id);
        $emissionFactorGroup->register(
            $request->name,
            new Gwp(
                gwpCo2: $request->gwpCo2,
                gwpCh4: $request->gwpCh4,
                gwpN2O: $request->gwpN2O,
            ),
            $isDefaultLocale ? null : $locale,
        );

        $this->emissionFactorGroupRepository->add($emissionFactorGroup);

        return new RegisterEmissionFactorGroupResponse(
            emissionFactorGroupId: $emissionFactorGroup->id(),
            name: $emissionFactorGroup->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterEmissionFactorGroupRequest;
    }
}
