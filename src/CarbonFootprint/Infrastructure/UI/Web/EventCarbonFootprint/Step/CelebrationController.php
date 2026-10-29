<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase\AnswerPhaseRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerPhase\AnswerPhaseService;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageExclusionHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\CelebrationStepView;
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

#[Route('/events/{eventId}/carbon-footprint/step/celebration', name: 'events_carbon_footprint_celebration')]
class CelebrationController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        StageAnswerHttpMapper $answerMapper,
        StageExclusionHttpMapper $exclusionMapper,
        AnswerPhaseService $answerPhase,
        GetPhaseService $getPhase,
        CelebrationStepView $stepView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if ($eventCarbonFootprint->status()->isCompleted()) {
            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId, 'step' => 'celebration']);
        }

        if (!$eventCarbonFootprint->status()->isAssemblyAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_assembly', ['eventId' => $eventId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('celebration_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerPhase(
                    new AnswerPhaseRequest($eventCarbonFootprint->id(), Phase::Celebration, $answerMapper->map($request->request->all(), Phase::Celebration->sections()), $exclusionMapper->map($request, Phase::Celebration)),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

                return $this->redirectToRoute('events_carbon_footprint_celebration', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_breakdown', ['eventId' => $eventId]);
        }

        $response = $getPhase(new GetPhaseRequest($eventCarbonFootprint->id(), Phase::Celebration), $loggedUser());

        return $this->render(
            '@carbon_footprint/event_carbon_footprints/step/celebration.html.twig',
            $stepView->build($eventCarbonFootprint, $response) + ['event' => $event, 'eventId' => $eventId],
        );
    }
}
