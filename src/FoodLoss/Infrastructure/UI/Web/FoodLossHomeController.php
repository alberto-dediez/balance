<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web;

use App\FoodLoss\Application\Dashboard\GetOwnerDashboardRequest;
use App\FoodLoss\Application\Dashboard\GetOwnerDashboardService;
use App\FoodLoss\Application\Dashboard\GetUserDashboardRequest;
use App\FoodLoss\Application\Dashboard\GetUserDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardFoodLossType;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardUserType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'food_loss_home', priority: 10)]
class FoodLossHomeController extends AbstractController
{
    use FilterTrait;

    public function __invoke(GetOwnerDashboardService $getFoodLossDashboard, GetUserDashboardService $getUserDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetOwnerDashboardRequest {
                $ownerRequest = new GetOwnerDashboardRequest(limit: 0);
                $ownerRequest->accountId = $loggedAccount()?->id();

                return $ownerRequest;
            }, DashboardFoodLossType::class);

            $response = $getFoodLossDashboard($filter, $loggedUser());

            return $this->render('@food_loss/home/dashboard.html.twig', [
                'dashboard' => $response,
                'form' => $form->createView(),
            ]);
        } catch (UnauthorizedActionException) {
        }

        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetUserDashboardRequest {
                $userRequest = new GetUserDashboardRequest(limit: 0);
                $userRequest->accountId = $loggedAccount()?->id();

                return $userRequest;
            }, DashboardUserType::class);

            $response = $getUserDashboard($filter, $loggedUser());

            return $this->render('@food_loss/home/user_dashboard.html.twig', [
                'dashboard' => $response,
                'form' => $form->createView(),
            ]);
        } catch (UnauthorizedActionException) {
        }

        return $this->render('@shared/home.html.twig');
    }
}
