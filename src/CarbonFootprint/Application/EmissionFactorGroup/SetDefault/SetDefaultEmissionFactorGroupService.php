<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\SetDefault;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SetDefaultEmissionFactorGroupService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private EmissionFactorGroupAuthorizer $authorizer,
    ) {}

    public function __invoke(SetDefaultEmissionFactorGroupRequest $request, User $user): SetDefaultEmissionFactorGroupResponse
    {
        $emissionFactorGroup = $this->emissionFactorGroupRepository->find($request->emissionFactorGroupId);

        if (null == $emissionFactorGroup) {
            throw new \InvalidArgumentException('EmissionFactorGroup not found');
        }

        $this->authorizer->denyUnlessCanDoThat($emissionFactorGroup, SecurityAction::Edit, $user);

        if (!$emissionFactorGroup->isDefault()) {
            $currentDefault = $this->emissionFactorGroupRepository->findDefault();

            if (null !== $currentDefault && !$currentDefault->id()->equals($emissionFactorGroup->id())) {
                $currentDefault->unsetAsDefault();
                $this->emissionFactorGroupRepository->add($currentDefault);
            }

            $emissionFactorGroup->setAsDefault();
            $this->emissionFactorGroupRepository->add($emissionFactorGroup);
        }

        return new SetDefaultEmissionFactorGroupResponse(
            emissionFactorGroupId: $emissionFactorGroup->id(),
            name: $emissionFactorGroup->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SetDefaultEmissionFactorGroupRequest;
    }
}
