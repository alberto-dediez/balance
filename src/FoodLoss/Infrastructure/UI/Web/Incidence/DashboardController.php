<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Incidence;

use App\FoodLoss\Application\Incidence\Dashboard\GetIncidenceDashboardRequest;
use App\FoodLoss\Application\Incidence\Dashboard\GetIncidenceDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardIncidencesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/incidences/dashboard', name: 'incidences_dashboard')]
class DashboardController extends AbstractController
{
    use FilterTrait;

    public function __invoke(GetIncidenceDashboardService $getIncidenceDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetIncidenceDashboardRequest {
                $request = new GetIncidenceDashboardRequest(limit: 0);
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, DashboardIncidencesType::class);

            $response = $getIncidenceDashboard($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/incidences/dashboard.html.twig', [
            'dashboard' => $response,
            'form' => $form->createView(),
        ]);
    }
}
