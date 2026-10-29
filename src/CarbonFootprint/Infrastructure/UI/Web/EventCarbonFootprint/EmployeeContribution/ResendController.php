<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\EmployeeContribution;

use App\CarbonFootprint\Application\EmployeeContribution\Resend\ResendEmployeeContributionNotificationRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Resend\ResendEmployeeContributionNotificationService;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/employee-contribution/{employeeContributionId}/resend', name: 'events_carbon_footprint_employee_contribution_resend', methods: ['GET'])]
class ResendController extends AbstractController
{
    public function __invoke(
        string $eventId,
        string $employeeContributionId,
        ResendEmployeeContributionNotificationService $resendNotification,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('employee_contribution_resend_' . $employeeContributionId, $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $resendNotification(
                new ResendEmployeeContributionNotificationRequest(new EmployeeContributionId($employeeContributionId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reenviar la notificación: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));
        }

        return $this->redirectToRoute('events_carbon_footprint_employees', ['eventId' => $eventId]);
    }
}
