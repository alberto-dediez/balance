<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\Remove;

use App\CarbonFootprint\Application\EmissionFactorGroup\EmissionFactorGroupAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveEmissionFactorGroupService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private EmissionFactorGroupRepository $repository, private EmissionFactorGroupAuthorizer $authorizer) {}

    public function __invoke(RemoveEmissionFactorGroupRequest $request, User $user): RemoveEmissionFactorGroupResponse
    {
        $emissionFactorGroup = $this->repository->find($request->emissionFactorGroupId);

        if (null == $emissionFactorGroup) {
            throw new \InvalidArgumentException('EmissionFactorGroup not found');
        }

        $this->authorizer->denyUnlessCanDoThat($emissionFactorGroup, SecurityAction::Remove, $user);

        $emissionFactorGroup->remove();

        $this->repository->add($emissionFactorGroup);

        return new RemoveEmissionFactorGroupResponse(
            emissionFactorGroupId: $emissionFactorGroup->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveEmissionFactorGroupRequest;
    }
}
