<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\RequestSignature\EmployeeEmailMissingException;
use App\FoodLoss\Application\TrainingAttendance\RequestSignature\RequestTrainingAttendanceSignaturesRequest;
use App\FoodLoss\Application\TrainingAttendance\RequestSignature\RequestTrainingAttendanceSignaturesService;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\RequestTrainingAttendanceSignatureType;
use App\FoodLoss\Infrastructure\UI\Web\TrainingSession\GetTrainingSessionFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/{trainingSessionId}/attendances/request-signature', name: 'training_attendances_request_signature')]
class RequestSignatureController extends AbstractController
{
    use RegisterTrait;
    use GetTrainingSessionFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        TrainingSessionRepository $trainingSessionRepository,
        RequestTrainingAttendanceSignaturesService $requestSignaturesService,
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

        $signatureRequest = new RequestTrainingAttendanceSignaturesRequest(
            trainingSessionId: new TrainingSessionId($trainingSessionId),
            accountId: $account->id(),
        );

        $form = $this->createForm(RequestTrainingAttendanceSignatureType::class, $signatureRequest);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($requestSignaturesService, $user): true {
                /** @var RequestTrainingAttendanceSignaturesRequest $data */
                $data = $form->getData();

                $requestSignaturesService($data, $user);

                $this->addFlash('success', new TranslatableMessage('Solicitudes de firma enviadas correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('training_attendances', ['trainingSessionId' => $trainingSessionId]);
            }
        } catch (EmployeeEmailMissingException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/training-attendance/request-signature.html.twig', [
            'trainingSession' => $trainingSession,
            'form' => $form->createView(),
        ]);
    }
}
