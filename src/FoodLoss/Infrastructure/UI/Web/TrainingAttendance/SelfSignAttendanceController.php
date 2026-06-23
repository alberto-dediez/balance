<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\RequestSignature\EmployeeEmailMissingException;
use App\FoodLoss\Application\TrainingAttendance\RequestSignature\RequestTrainingAttendanceSignaturesRequest;
use App\FoodLoss\Application\TrainingAttendance\RequestSignature\RequestTrainingAttendanceSignaturesService;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\SelfSignTrainingAttendanceType;
use App\FoodLoss\Infrastructure\UI\Web\TrainingSession\GetTrainingSessionFromIdTrait;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/training-sessions/{trainingSessionId}/attendances/self-sign', name: 'training_attendances_self_sign')]
class SelfSignAttendanceController extends AbstractController
{
    use GetTrainingSessionFromIdTrait;

    public function __invoke(
        string $trainingSessionId,
        TrainingSessionRepository $trainingSessionRepository,
        EmployeeRepository $employeeRepository,
        RequestTrainingAttendanceSignaturesService $requestSignaturesService,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $account = $loggedAccount();

        assert($account instanceof Account);

        $trainingSession = $this->getTrainingSessionFromId($trainingSessionId, $trainingSessionRepository);

        $employees = $employeeRepository->findByFilter(
            new EmployeeFilter(userId: $user->id(), accountId: $account->id(), limit: 1),
        );

        if ([] === $employees) {
            throw $this->createNotFoundException('No se ha encontrado un empleado asociado a tu usuario en esta cuenta.');
        }

        $employee = reset($employees);

        $form = $this->createForm(SelfSignTrainingAttendanceType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $signatureRequest = new RequestTrainingAttendanceSignaturesRequest(
                    trainingSessionId: new TrainingSessionId($trainingSessionId),
                    employeeIds: [$employee->id()],
                    accountId: $account->id(),
                );

                $requestSignaturesService($signatureRequest, $user);

                $this->addFlash('success', new TranslatableMessage('Se ha enviado la solicitud de firma correctamente'));
            } catch (EmployeeEmailMissingException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            return $this->redirectToRoute('training_sessions');
        }

        return $this->render('@food_loss/training-attendance/self-sign.html.twig', [
            'trainingSession' => $trainingSession,
            'employee' => $employee,
            'form' => $form->createView(),
        ]);
    }
}
