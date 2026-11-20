<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest\List;

use App\Balance\Application\ContactRequest\ContactRequestAuthorizer;
use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListContactRequestsService
{
    public function __construct(
        private ContactRequestRepository $repository,
        private ContactRequestAuthorizer $authorizer,
    ) {}

    public function __invoke(ListContactRequestsRequest $request, User $user): ListContactRequestsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new ContactRequest(id: ContactRequestId::generate()),
            SecurityAction::List,
            $user,
        );

        return new ListContactRequestsResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
