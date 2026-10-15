<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Remove;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveUseProfileService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private UseProfileRepository $repository, private UseProfileAuthorizer $authorizer) {}

    public function __invoke(RemoveUseProfileRequest $request, User $user): RemoveUseProfileResponse
    {
        $useProfile = $this->repository->find($request->useProfileId);

        if (null == $useProfile) {
            throw new \InvalidArgumentException('UseProfile not found');
        }

        $this->authorizer->denyUnlessCanDoThat($useProfile, SecurityAction::Remove, $user);

        $useProfile->remove();

        $this->repository->add($useProfile);

        return new RemoveUseProfileResponse(
            useProfileId: $useProfile->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveUseProfileRequest;
    }
}
