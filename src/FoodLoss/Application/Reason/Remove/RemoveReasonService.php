<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Reason\Remove;

use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveReasonService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private ReasonRepository $repository, private ReasonAuthorizer $authorizer) {}

    public function __invoke(RemoveReasonRequest $request, User $user): RemoveReasonResponse
    {
        $reason = $this->repository->find($request->reasonId);

        if (null == $reason) {
            throw new \InvalidArgumentException('Reason not found');
        }

        $this->authorizer->denyUnlessCanDoThat($reason, SecurityAction::Remove, $user);

        $reason->remove();

        $this->repository->add($reason);

        return new RemoveReasonResponse(
            reasonId: $reason->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveReasonRequest;
    }
}
