<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Remove;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveProjectService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private ProjectRepository $repository, private ProjectAuthorizer $authorizer) {}

    public function __invoke(RemoveProjectRequest $request, User $user): RemoveProjectResponse
    {
        $project = $this->repository->find($request->projectId);

        if (null == $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        $this->authorizer->denyUnlessCanDoThat($project, SecurityAction::Remove, $user);

        $project->remove();

        $this->repository->add($project);

        return new RemoveProjectResponse(
            projectId: $project->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveProjectRequest;
    }
}
