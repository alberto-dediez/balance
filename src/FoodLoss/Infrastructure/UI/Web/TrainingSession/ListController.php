<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\TrainingSession;

use App\FoodLoss\Application\TrainingSession\List\ListTrainingSessionsRequest;
use App\FoodLoss\Application\TrainingSession\List\ListTrainingSessionsService;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Infrastructure\Symfony\Form\ListTrainingSessionsType;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
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
        EmployeeRepository $employeeRepository,
        TrainingAttendanceRepository $trainingAttendanceRepository,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $accountId = $loggedAccount()?->id();

        try {
            [$filter, $form] = $this->filter($request, function () use ($accountId): ListTrainingSessionsRequest {
                $filterRequest = new ListTrainingSessionsRequest();
                $filterRequest->accountId = $accountId;

                return $filterRequest;
            }, ListTrainingSessionsType::class);

            $response = $listTrainingSessions($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $myAttendances = [];
        $employees = $employeeRepository->findByFilter(new EmployeeFilter(userId: $user->id(), accountId: $accountId, limit: 1));

        if ([] !== $employees) {
            $employee = reset($employees);
            $attendances = $trainingAttendanceRepository->findByFilter(
                new TrainingAttendanceFilter(employeeId: $employee->id(), accountId: $accountId, limit: 0),
            );

            foreach ($attendances as $attendance) {
                $myAttendances[(string) $attendance->trainingSessionId()] = $attendance;
            }
        }

        return $this->render('@food_loss/training-session/list.html.twig', [
            'trainingSessions' => $response->trainingSessions,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
            'dummy' => new TrainingSession(id: TrainingSessionId::generate(), accountId: $accountId),
            'dummyAttendance' => new TrainingAttendance(id: TrainingAttendanceId::generate(), accountId: $accountId),
            'myAttendances' => $myAttendances,
        ]);
    }
}
