<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\List\ListTrainingAttendancesRequest;
use App\FoodLoss\Application\TrainingAttendance\List\ListTrainingAttendancesService;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\UI\Web\TrainingSession\GetTrainingSessionFromIdTrait;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/training-sessions/{trainingSessionId}/attendances', name: 'training_attendances')]
class ListController extends AbstractController
{
    use GetTrainingSessionFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        TrainingSessionRepository $trainingSessionRepository,
        ListTrainingAttendancesService $listTrainingAttendances,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $trainingSession = $this->getTrainingSessionFromId($trainingSessionId, $trainingSessionRepository);

        $this->denyAccessUnlessGranted(
            Action::List->value,
            new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $account?->id()),
        );

        try {
            $response = $listTrainingAttendances(
                new ListTrainingAttendancesRequest(
                    trainingSessionId: new TrainingSessionId($trainingSessionId),
                    accountId: $account?->id(),
                ),
                $user,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/training-attendance/list.html.twig', [
            'trainingSession' => $trainingSession,
            'summaries' => $response->summaries,
            'dummy' => new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $account?->id()),
        ]);
    }
}
