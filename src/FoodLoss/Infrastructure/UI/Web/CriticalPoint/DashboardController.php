<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\CriticalPoint;

use App\FoodLoss\Application\CriticalPoint\Dashboard\GetCriticalPointDashboardRequest;
use App\FoodLoss\Application\CriticalPoint\Dashboard\GetCriticalPointDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardCriticalPointsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/critical-points/dashboard', name: 'critical_points_dashboard')]
class DashboardController extends AbstractController
{
    use FilterTrait;

    public function __invoke(GetCriticalPointDashboardService $getCriticalPointDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetCriticalPointDashboardRequest {
                $request = new GetCriticalPointDashboardRequest(limit: 0);
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, DashboardCriticalPointsType::class);

            $response = $getCriticalPointDashboard($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/critical-points/dashboard.html.twig', [
            'dashboard' => $response,
            'form' => $form->createView(),
        ]);
    }
}
