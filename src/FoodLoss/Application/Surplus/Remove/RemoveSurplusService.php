<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus\Remove;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveSurplusService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private SurplusRepository $repository, private SurplusAuthorizer $authorizer) {}

    public function __invoke(RemoveSurplusRequest $request, User $user): RemoveSurplusResponse
    {
        $surplus = $this->repository->find($request->surplusId);

        if (null == $surplus) {
            throw new \InvalidArgumentException('Surplus not found');
        }

        $this->authorizer->denyUnlessCanDoThat($surplus, SecurityAction::Remove, $user);

        $surplus->remove();

        $this->repository->add($surplus);

        return new RemoveSurplusResponse(
            surplusId: $surplus->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveSurplusRequest;
    }
}
