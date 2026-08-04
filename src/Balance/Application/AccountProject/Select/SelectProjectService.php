<?php

declare(strict_types=1);

namespace App\Balance\Application\AccountProject\Select;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SelectProjectService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private AccountProjectRepository $accountProjectRepository,
        private ProjectRepository $projectRepository,
        private AccountProjectAuthorizer $authorizer,
    ) {}

    public function __invoke(SelectProjectRequest $request, User $user): SelectProjectResponse
    {
        $id = AccountProjectId::generate();

        $this->authorizer->denyUnlessCanDoThat(
            new AccountProject(id: $id, accountId: $request->accountId),
            SecurityAction::Register,
            $user,
        );

        $project = $this->projectRepository->find($request->projectId);

        if (null === $project) {
            throw new \InvalidArgumentException('Project not found');
        }

        $existing = $this->accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $request->accountId, projectId: $request->projectId, limit: 1),
        );

        if (count($existing) > 0) {
            $accountProject = $existing[0];

            return new SelectProjectResponse(
                accountProjectId: $accountProject->id(),
                accountId: $request->accountId,
                projectId: $request->projectId,
            );
        }

        $accountProject = new AccountProject(id: $id);
        $accountProject->select($request->accountId, $request->projectId);

        $this->accountProjectRepository->add($accountProject);

        return new SelectProjectResponse(
            accountProjectId: $accountProject->id(),
            accountId: $request->accountId,
            projectId: $request->projectId,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SelectProjectRequest;
    }
}
