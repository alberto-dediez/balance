<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Table\ActivityRowParser;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials\AnswerMaterialsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerMaterials\AnswerMaterialsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\Step\MaterialsStepViewBuilder;
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

#[Route('/events/{eventId}/carbon-footprint/step/materials', name: 'events_carbon_footprint_materials')]
class MaterialsController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        ActivityRowParser $parser,
        AnswerMaterialsService $answerMaterials,
        MaterialsStepViewBuilder $viewBuilder,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('materials_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $rows = array_merge(
                $parser->parse($request->request->all('materials'), ActivityType::Material, withCondition: true),
                $parser->parse($request->request->all('cateringItems'), ActivityType::CateringItem, withCondition: true),
                $parser->parse($request->request->all('merchandisingItems'), ActivityType::MerchandisingItem, withCondition: true),
            );

            try {
                $answerMaterials(
                    new AnswerMaterialsRequest($eventCarbonFootprint->id(), $rows),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido guardar las actividades: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('events_carbon_footprint_materials', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_assembly', ['eventId' => $eventId]);
        }

        return $this->render(
            '@carbon_footprint/event_carbon_footprints/step/materials.html.twig',
            $viewBuilder->build($eventCarbonFootprint) + ['event' => $event, 'eventId' => $eventId],
        );
    }
}
