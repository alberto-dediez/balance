<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\List;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;

class ListAlertsService
{
    public function __construct(private readonly AlertRepository $repository, private readonly AlertAuthorizer $authorizer) {}

    public function __invoke(ListAlertsRequest $request, User $user): ListAlertsResponse
    {
        $alert = new Alert(id: AlertId::generate());

        if (null !== $request->accountId) {
            $alert = new Alert(id: AlertId::generate(), accountId: $request->accountId);
        }

        $this->authorizer->denyUnlessCanDoThat($alert, Action::List, $user);

        $alerts = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListAlertsResponse($alerts, $totalResults);
    }
}
