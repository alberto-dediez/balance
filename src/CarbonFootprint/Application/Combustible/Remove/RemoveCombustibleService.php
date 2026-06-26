<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Remove;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveCombustibleService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private CombustibleRepository $repository, private CombustibleAuthorizer $authorizer) {}

    public function __invoke(RemoveCombustibleRequest $request, User $user): RemoveCombustibleResponse
    {
        $combustible = $this->repository->find($request->combustibleId);

        if (null == $combustible) {
            throw new \InvalidArgumentException('Combustible not found');
        }

        $this->authorizer->denyUnlessCanDoThat($combustible, SecurityAction::Remove, $user);

        $combustible->remove();

        $this->repository->add($combustible);

        return new RemoveCombustibleResponse(
            combustibleId: $combustible->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCombustibleRequest;
    }
}
