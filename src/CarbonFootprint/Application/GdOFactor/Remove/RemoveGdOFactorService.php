<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Remove;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveGdOFactorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private GdOFactorRepository $repository, private GdOFactorAuthorizer $authorizer) {}

    public function __invoke(RemoveGdOFactorRequest $request, User $user): RemoveGdOFactorResponse
    {
        $gdOFactor = $this->repository->find($request->gdOFactorId);

        if (null == $gdOFactor) {
            throw new \InvalidArgumentException('GdOFactor not found');
        }

        $this->authorizer->denyUnlessCanDoThat($gdOFactor, SecurityAction::Remove, $user);

        $gdOFactor->remove();

        $this->repository->add($gdOFactor);

        return new RemoveGdOFactorResponse(
            gdOFactorId: $gdOFactor->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveGdOFactorRequest;
    }
}
