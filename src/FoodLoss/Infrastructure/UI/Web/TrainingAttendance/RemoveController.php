<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\Remove\RemoveTrainingAttendanceRequest;
use App\FoodLoss\Application\TrainingAttendance\Remove\RemoveTrainingAttendanceService;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/{trainingSessionId}/attendances/{trainingAttendanceId}/remove', name: 'training_attendances_remove')]
class RemoveController extends AbstractController
{
    use GetTrainingAttendanceFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        string $trainingAttendanceId,
        TrainingAttendanceRepository $trainingAttendanceRepository,
        RemoveTrainingAttendanceService $removeTrainingAttendance,
        LoggedUser $loggedUser,
    ): Response {
        $attendance = $this->getTrainingAttendanceFromId($trainingAttendanceId, $trainingAttendanceRepository);

        $this->denyAccessUnlessGranted(Action::Remove->value, $attendance);

        try {
            $removeTrainingAttendance(new RemoveTrainingAttendanceRequest($attendance->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la asistencia'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la asistencia'));
        }

        return $this->redirectToRoute('training_attendances', ['trainingSessionId' => $trainingSessionId]);
    }
}
