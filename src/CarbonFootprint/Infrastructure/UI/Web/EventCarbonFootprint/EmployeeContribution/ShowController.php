<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\EmployeeContribution;

use App\CarbonFootprint\Application\EmployeeContribution\Show\GetEmployeeContributionDetailRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Show\GetEmployeeContributionDetailService;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/employee-contribution/{employeeContributionId}', name: 'events_carbon_footprint_employee_contribution_show', methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(
        string $eventId,
        string $employeeContributionId,
        GetEmployeeContributionDetailService $getEmployeeContributionDetail,
        MobilityRepository $mobilityRepository,
        EventRepository $eventRepository,
        LoggedUser $loggedUser,
    ): Response {
        try {
            $response = $getEmployeeContributionDetail(
                new GetEmployeeContributionDetailRequest(new EmployeeContributionId($employeeContributionId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Contribución de empleado no encontrada');
        }

        $mobilitiesById = [];
        foreach ($mobilityRepository->findByFilter(new MobilityFilter(limit: 0)) as $mobility) {
            $mobilitiesById[(string) $mobility->id()] = $mobility;
        }

        return $this->render('@carbon_footprint/event_carbon_footprints/employee_contribution/show.html.twig', [
            'eventId' => $eventId,
            'event' => $eventRepository->find(new EventId($eventId)),
            'employeeContribution' => $response->employeeContribution,
            'mobilitiesById' => $mobilitiesById,
        ]);
    }
}
