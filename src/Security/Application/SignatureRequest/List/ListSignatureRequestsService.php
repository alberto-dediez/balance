<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest\List;

use App\Security\Application\Action as SecurityAction;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use App\Security\Domain\Model\User\User;

class ListSignatureRequestsService
{
    public function __construct(
        private SignatureRequestRepository $repository,
        private SignatureRequestAuthorizer $authorizer,
    ) {}

    public function __invoke(ListSignatureRequestsRequest $request, User $user): ListSignatureRequestsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new SignatureRequest(id: SignatureRequestId::generate()), SecurityAction::List, $user);

        $signatureRequests = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListSignatureRequestsResponse($signatureRequests, $totalResults);
    }
}
