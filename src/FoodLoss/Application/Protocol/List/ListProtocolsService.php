<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\List;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListProtocolsService
{
    public function __construct(
        private ProtocolRepository $repository,
        private ProtocolAuthorizer $authorizer,
    ) {}

    public function __invoke(ListProtocolsRequest $request, User $user): ListProtocolsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new Protocol(id: ProtocolId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $protocols = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListProtocolsResponse($protocols, $totalResults);
    }
}
