<?php

declare(strict_types=1);

namespace App\Balance\Application\AccountProject\List;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListAccountProjectsService
{
    public function __construct(
        private AccountProjectRepository $repository,
        private AccountProjectAuthorizer $authorizer,
    ) {}

    public function __invoke(ListAccountProjectsRequest $request, User $user): ListAccountProjectsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new AccountProject(id: AccountProjectId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $accountProjects = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListAccountProjectsResponse($accountProjects, $totalResults);
    }
}
