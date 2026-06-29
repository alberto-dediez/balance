<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\List;

use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListProvidersService
{
    public function __construct(private ProviderRepository $repository, private ProviderAuthorizer $authorizer) {}

    public function __invoke(ListProvidersRequest $request, User $user): ListProvidersResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Provider(
            id: ProviderId::generate(),
            accountId: $request->accountId,
        ), SecurityAction::List, $user);

        $providers = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListProvidersResponse($providers, $totalResults);
    }
}
