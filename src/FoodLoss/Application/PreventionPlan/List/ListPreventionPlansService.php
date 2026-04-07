<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\List;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListPreventionPlansService
{
    public function __construct(
        private PreventionPlanRepository $repository,
        private PreventionPlanAuthorizer $authorizer,
    ) {}

    public function __invoke(ListPreventionPlansRequest $request, User $user): ListPreventionPlansResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new PreventionPlan(id: PreventionPlanId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $preventionPlans = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListPreventionPlansResponse($preventionPlans, $totalResults);
    }
}
