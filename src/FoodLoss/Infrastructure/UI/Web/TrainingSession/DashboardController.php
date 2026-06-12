<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingSession;

use App\FoodLoss\Application\TrainingSession\Dashboard\GetTrainingDashboardRequest;
use App\FoodLoss\Application\TrainingSession\Dashboard\GetTrainingDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardTrainingsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trainings/dashboard', name: 'trainings_dashboard')]
#[IsGranted('ROLE_OWNER')]
class DashboardController extends AbstractController
{
    use FilterTrait;

    public function __invoke(GetTrainingDashboardService $getTrainingDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetTrainingDashboardRequest {
                $request = new GetTrainingDashboardRequest(limit: 0);
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, DashboardTrainingsType::class);

            $response = $getTrainingDashboard($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/training-session/dashboard.html.twig', [
            'dashboard' => $response,
            'form' => $form->createView(),
        ]);
    }
}
