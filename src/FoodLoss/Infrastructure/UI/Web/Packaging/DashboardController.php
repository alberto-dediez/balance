<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Packaging;

use App\FoodLoss\Application\Packaging\Dashboard\GetDoggyBagDashboardRequest;
use App\FoodLoss\Application\Packaging\Dashboard\GetDoggyBagDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardDoggyBagType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/doggy-bag/dashboard', name: 'doggy_bag_dashboard')]
class DashboardController extends AbstractController
{
    use FilterTrait;

    public function __invoke(GetDoggyBagDashboardService $getDoggyBagDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetDoggyBagDashboardRequest {
                $request = new GetDoggyBagDashboardRequest(limit: 0);
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, DashboardDoggyBagType::class);

            $response = $getDoggyBagDashboard($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/packagings/dashboard.html.twig', [
            'dashboard' => $response,
            'form' => $form->createView(),
        ]);
    }
}
