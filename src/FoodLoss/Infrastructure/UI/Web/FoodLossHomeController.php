<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web;

use App\FoodLoss\Application\Dashboard\GetFoodLossDashboardRequest;
use App\FoodLoss\Application\Dashboard\GetFoodLossDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardFoodLossType;
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

    public function __invoke(GetFoodLossDashboardService $getFoodLossDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetFoodLossDashboardRequest {
                $request = new GetFoodLossDashboardRequest(limit: 0);
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, DashboardFoodLossType::class);

            $response = $getFoodLossDashboard($filter, $loggedUser());
        } catch (UnauthorizedActionException) {
            return $this->render('@shared/home.html.twig');
        }

        return $this->render('@food_loss/home/dashboard.html.twig', [
            'dashboard' => $response,
            'form' => $form->createView(),
        ]);
    }
}
