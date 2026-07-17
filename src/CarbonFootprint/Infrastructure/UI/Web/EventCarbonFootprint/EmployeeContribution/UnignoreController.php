<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\EmployeeContribution;

use App\CarbonFootprint\Application\EmployeeContribution\Ignore\UnignoreEmployeeContributionRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Ignore\UnignoreEmployeeContributionService;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/employee-contribution/{employeeContributionId}/unignore', name: 'events_carbon_footprint_employee_contribution_unignore', methods: ['GET'])]
class UnignoreController extends AbstractController
{
    public function __invoke(
        string $eventId,
        string $employeeContributionId,
        UnignoreEmployeeContributionService $unignoreEmployeeContribution,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('employee_contribution_unignore_' . $employeeContributionId, $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $unignoreEmployeeContribution(
                new UnignoreEmployeeContributionRequest(new EmployeeContributionId($employeeContributionId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('events_carbon_footprint_employees', ['eventId' => $eventId]);
    }
}
