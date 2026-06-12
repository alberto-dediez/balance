<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\List;

use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListIncidencesService
{
    public function __construct(
        private IncidenceRepository $incidenceRepository,
        private IncidenceAuthorizer $authorizer,
    ) {}

    public function __invoke(ListIncidencesRequest $request, User $user): ListIncidencesResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Incidence(id: IncidenceId::generate(), accountId: $request->accountId), SecurityAction::List, $user);

        $incidences = $this->incidenceRepository->findByFilter($request);
        $totalResults = $this->incidenceRepository->totalResultsByFilter($request);

        return new ListIncidencesResponse($incidences, $totalResults);
    }
}
