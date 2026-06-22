<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess\CancelTrainingAttendanceSignatureProcessRequest;
use App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess\CancelTrainingAttendanceSignatureProcessService;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\CancelSignatureProcessType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/{trainingSessionId}/attendances/{trainingAttendanceId}/cancel-signature', name: 'training_attendances_cancel_signature')]
class CancelSignatureController extends AbstractController
{
    use GetTrainingAttendanceFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        string $trainingAttendanceId,
        TrainingAttendanceRepository $trainingAttendanceRepository,
        CancelTrainingAttendanceSignatureProcessService $cancelSignatureProcess,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $attendance = $this->getTrainingAttendanceFromId($trainingAttendanceId, $trainingAttendanceRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $attendance);

        $signatureRequestId = $attendance->signatureRequestId();

        if (null === $signatureRequestId || !$attendance->isInSignatureProcess()) {
            $this->addFlash('warning', new TranslatableMessage('Esta asistencia no tiene un proceso de firma pendiente'));

            return $this->redirectToRoute('training_attendances', ['trainingSessionId' => $trainingSessionId]);
        }

        $form = $this->createForm(CancelSignatureProcessType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $cancelSignatureProcess(
                    new CancelTrainingAttendanceSignatureProcessRequest($signatureRequestId),
                    $loggedUser(),
                );

                $this->addFlash('success', new TranslatableMessage('Se ha cancelado el proceso de firma correctamente'));
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido cancelar el proceso de firma'));
            }

            return $this->redirectToRoute('training_attendances', ['trainingSessionId' => $trainingSessionId]);
        }

        return $this->render('@food_loss/training-attendance/cancel-signature.html.twig', [
            'trainingSessionId' => $trainingSessionId,
            'attendance' => $attendance,
            'form' => $form->createView(),
        ]);
    }
}
