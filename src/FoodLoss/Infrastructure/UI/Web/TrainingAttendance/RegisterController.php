<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\Register\RegisterTrainingAttendanceRequest;
use App\FoodLoss\Application\TrainingAttendance\Register\RegisterTrainingAttendanceService;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterTrainingAttendanceType;
use App\FoodLoss\Infrastructure\UI\Web\TrainingSession\GetTrainingSessionFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/{trainingSessionId}/attendances/register', name: 'training_attendances_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;
    use GetTrainingSessionFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        TrainingSessionRepository $trainingSessionRepository,
        RegisterTrainingAttendanceService $registerTrainingAttendance,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        $trainingSession = $this->getTrainingSessionFromId($trainingSessionId, $trainingSessionRepository);

        $this->denyAccessUnlessGranted(
            SecurityAction::Register->value,
            new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $account?->id()),
        );

        assert($account instanceof Account);

        $registerRequest = new RegisterTrainingAttendanceRequest(
            trainingSessionId: new TrainingSessionId($trainingSessionId),
            accountId: $account->id(),
            attendedAt: $trainingSession->sessionDate(),
        );

        $form = $this->createForm(RegisterTrainingAttendanceType::class, $registerRequest);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerTrainingAttendance, $user): true {
                /** @var RegisterTrainingAttendanceRequest $registerRequest */
                $registerRequest = $form->getData();

                /** @var UploadedFile $file */
                $file = $form->get('attendanceSheet')->getData();

                $registerRequest->temporaryFilePath = $file->getPathname();
                $registerRequest->fileExtension = (string) $file->guessExtension();

                $registerTrainingAttendance($registerRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Asistencias registradas correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('training_attendances', ['trainingSessionId' => $trainingSessionId]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/training-attendance/register.html.twig', [
            'trainingSession' => $trainingSession,
            'form' => $form->createView(),
        ]);
    }
}
