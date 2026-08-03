<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\List;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListProjectsService
{
    public function __construct(private ProjectRepository $repository, private ProjectAuthorizer $authorizer) {}

    public function __invoke(ListProjectsRequest $request, User $user): ListProjectsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Project(id: ProjectId::generate()), SecurityAction::List, $user);

        $projects = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListProjectsResponse($projects, $totalResults);
    }
}
