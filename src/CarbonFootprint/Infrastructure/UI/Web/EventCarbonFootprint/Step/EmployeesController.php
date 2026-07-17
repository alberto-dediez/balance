<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees\AssignedEmployee;
use App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees\AssignEmployeesRequest;
use App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees\AssignEmployeesService;
use App\CarbonFootprint\Application\EmployeeParticipation\Register\RegisterEmployeeParticipationRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\Register\RegisterEmployeeParticipationService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\EmployeesStepView;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/step/employees', name: 'events_carbon_footprint_employees')]
class EmployeesController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        RegisterEmployeeParticipationService $registerEmployeeParticipation,
        AssignEmployeesService $assignEmployees,
        EmployeesStepView $stepView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if ($eventCarbonFootprint->status()->isCompleted()) {
            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId, 'step' => 'employees']);
        }

        if (!$eventCarbonFootprint->status()->isProvidersAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_providers', ['eventId' => $eventId]);
        }

        $registerEmployeeParticipation(new RegisterEmployeeParticipationRequest($eventCarbonFootprint->id()), $loggedUser());

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('employees_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $employeeIds = $request->request->all('employees')['employeeIds'] ?? [];

            $assignedEmployees = [];
            foreach ($employeeIds as $employeeId) {
                if (!is_string($employeeId) || '' === $employeeId) {
                    continue;
                }

                $assignedEmployees[] = new AssignedEmployee(new EmployeeId($employeeId));
            }

            $totalEmployees = $request->request->getInt('totalEmployees');
            $confidenceLevel = ConfidenceLevel::tryFrom($request->request->getInt('confidenceLevel'));

            if ($totalEmployees < 1 || null === $confidenceLevel) {
                $this->addFlash('danger', new TranslatableMessage('Introduce un número total de empleados válido y un nivel de confianza.'));

                return $this->redirectToRoute('events_carbon_footprint_employees', ['eventId' => $eventId]);
            }

            try {
                $assignEmployees(
                    new AssignEmployeesRequest($eventCarbonFootprint->id(), $assignedEmployees, $totalEmployees, $confidenceLevel),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido asignar los empleados: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('events_carbon_footprint_employees', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_attendees', ['eventId' => $eventId]);
        }

        return $this->render(
            '@carbon_footprint/event_carbon_footprints/step/employees.html.twig',
            $stepView->build($eventCarbonFootprint, $loggedUser()) + ['event' => $event, 'eventId' => $eventId],
        );
    }
}
