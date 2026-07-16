<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase\AnswerPhaseRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase\AnswerPhaseService;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\PhaseAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\PhaseExclusionHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\AssemblyBreakdownStepView;
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
        PhaseAnswerHttpMapper $answerMapper,
        PhaseExclusionHttpMapper $exclusionMapper,
        AnswerPhaseService $answerPhase,
        GetPhaseService $getPhase,
        AssemblyBreakdownStepView $stepView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if ($eventCarbonFootprint->status()->isCompleted()) {
            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId, 'step' => 'assembly']);
        }

        if (!$eventCarbonFootprint->status()->isMaterialsAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_materials', ['eventId' => $eventId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('assembly_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerPhase(
                    new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Assembly, $answerMapper->map($request, Phase::Assembly), $exclusionMapper->map($request, Phase::Assembly)),
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

        $response = $getPhase(new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Assembly), $loggedUser());

        return $this->render(
            '@carbon_footprint/event_carbon_footprints/step/assembly.html.twig',
            $stepView->build($eventCarbonFootprint, $response) + ['event' => $event, 'eventId' => $eventId],
        );
    }
}
