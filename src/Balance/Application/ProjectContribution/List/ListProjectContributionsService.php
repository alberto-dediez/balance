<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\List;

use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\ProjectContribution\DistributedContributionsResolver;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListProjectContributionsService
{
    public function __construct(
        private ProjectContributionRepository $repository,
        private ProjectContributionAuthorizer $authorizer,
        private ?DistributedContributionsResolver $distributedContributionsResolver = null,
    ) {}

    public function __invoke(ListProjectContributionsRequest $request, User $user): ListProjectContributionsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new ProjectContribution(id: ProjectContributionId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $filter = clone $request;

        $this->applyScope($filter, $user);

        return new ListProjectContributionsResponse(
            $this->repository->findByFilter($filter),
            $this->repository->totalResultsByFilter($filter),
        );
    }

    private function applyScope(ListProjectContributionsRequest $filter, User $user): void
    {
        $accountId = $filter->accountId;

        if (!$accountId instanceof AccountId || !$this->distributedContributionsResolver instanceof DistributedContributionsResolver) {
            return;
        }

        (new ProjectContributionsListScope($this->distributedContributionsResolver))->apply(
            $filter,
            $this->authorizer->isActiveDistributorOwner($user, $accountId),
        );
    }
}
