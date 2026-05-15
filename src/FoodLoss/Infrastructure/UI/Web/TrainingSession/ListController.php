<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingSession;

use App\FoodLoss\Application\TrainingSession\List\ListTrainingSessionsRequest;
use App\FoodLoss\Application\TrainingSession\List\ListTrainingSessionsService;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Infrastructure\Symfony\Form\ListTrainingSessionsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/training-sessions', name: 'training_sessions')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListTrainingSessionsService $listTrainingSessions,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();

        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): ListTrainingSessionsRequest {
                $filterRequest = new ListTrainingSessionsRequest();
                $filterRequest->accountId = $loggedAccount()?->id();

                return $filterRequest;
            }, ListTrainingSessionsType::class);

            $response = $listTrainingSessions($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/training-session/list.html.twig', [
            'trainingSessions' => $response->trainingSessions,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
            'dummy' => new TrainingSession(id: TrainingSessionId::generate(), accountId: $loggedAccount()?->id()),
            'dummyAttendance' => new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $loggedAccount()?->id()),
        ]);
    }
}
