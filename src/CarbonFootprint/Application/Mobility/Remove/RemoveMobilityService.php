<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Remove;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMobilityService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private MobilityRepository $repository, private MobilityAuthorizer $authorizer) {}

    public function __invoke(RemoveMobilityRequest $request, User $user): RemoveMobilityResponse
    {
        $mobility = $this->repository->find($request->mobilityId);

        if (null == $mobility) {
            throw new \InvalidArgumentException('Mobility not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mobility, SecurityAction::Remove, $user);

        $mobility->remove();

        $this->repository->add($mobility);

        return new RemoveMobilityResponse(
            mobilityId: $mobility->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMobilityRequest;
    }
}
