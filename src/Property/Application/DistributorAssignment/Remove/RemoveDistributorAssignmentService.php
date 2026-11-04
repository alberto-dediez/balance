<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment\Remove;

use App\Property\Application\DistributorAssignment\DistributorAssignmentAuthorizer;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveDistributorAssignmentService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly DistributorAssignmentRepository $repository,
        private readonly DistributorAssignmentAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveDistributorAssignmentRequest $request, User $user): RemoveDistributorAssignmentResponse
    {
        $assignment = $this->repository->find($request->id);

        if (null === $assignment) {
            throw new \InvalidArgumentException('Distributor assignment not found');
        }

        $this->authorizer->denyUnlessCanDoThat($assignment, Action::Remove, $user);

        $assignment->remove($user->id(), $request->notifyDistributor && !$this->authorizer->isAdmin($user));

        $this->repository->add($assignment);

        return new RemoveDistributorAssignmentResponse($assignment->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveDistributorAssignmentRequest;
    }
}
