<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\Dashboard\GetPreventionPlanDashboardRequest;
use App\FoodLoss\Application\PreventionPlan\Dashboard\GetPreventionPlanDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardPreventionPlansType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prevention-plans/dashboard', name: 'prevention_plans_dashboard')]
class DashboardController extends AbstractController
{
    use FilterTrait;

    public function __invoke(GetPreventionPlanDashboardService $getPreventionPlanDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetPreventionPlanDashboardRequest {
                $request = new GetPreventionPlanDashboardRequest(limit: 0);
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, DashboardPreventionPlansType::class);

            $response = $getPreventionPlanDashboard($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/prevention-plan/dashboard.html.twig', [
            'dashboard' => $response,
            'form' => $form->createView(),
        ]);
    }
}
