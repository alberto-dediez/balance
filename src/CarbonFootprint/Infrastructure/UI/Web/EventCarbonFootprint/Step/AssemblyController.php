<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Table\ActivityRowParser;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerAssembly\AnswerAssemblyRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerAssembly\AnswerAssemblyService;
use App\CarbonFootprint\Application\EventCarbonFootprint\Step\AssemblyStepViewBuilder;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/step/assembly', name: 'events_carbon_footprint_assembly')]
class AssemblyController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        ActivityRowParser $parser,
        AnswerAssemblyService $answerAssembly,
        AssemblyStepViewBuilder $viewBuilder,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if (!$eventCarbonFootprint->status()->isMaterialsAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_materials', ['eventId' => $eventId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('assembly_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerAssembly(
                    new AnswerAssemblyRequest(
                        eventCarbonFootprintId: $eventCarbonFootprint->id(),
                        fixedFacilitiesRows: $parser->parse($request->request->all('combustibles'), ActivityType::Combustible),
                        freightTransportRows: $parser->parse($request->request->all('roadTransports'), ActivityType::RoadTransport),
                        machineryOperationRows: array_merge(
                            $parser->parse($request->request->all('machineryFuels'), ActivityType::MachineryFuel),
                            $parser->parse($request->request->all('machineryElectricity'), ActivityType::Electricity, hasSecondaryItemId: true),
                        ),
                        buildingElectricityRows: $parser->parse($request->request->all('buildingElectricity'), ActivityType::Electricity, hasSecondaryItemId: true),
                        accommodationRows: $parser->parse($request->request->all('overnightStays'), ActivityType::OvernightStay),
                        mobilityRows: $parser->parse($request->request->all('mobilities'), ActivityType::Mobility),
                        wasteManagementRows: $parser->parse($request->request->all('wastes'), ActivityType::Waste),
                    ),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('events_carbon_footprint_assembly', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_celebration', ['eventId' => $eventId]);
        }

        return $this->render(
            '@carbon_footprint/event_carbon_footprints/step/assembly.html.twig',
            $viewBuilder->build($eventCarbonFootprint) + ['event' => $event, 'eventId' => $eventId],
        );
    }
}
