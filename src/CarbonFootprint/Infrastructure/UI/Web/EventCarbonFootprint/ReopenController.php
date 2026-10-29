<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/reopen', name: 'events_carbon_footprint_reopen', methods: ['POST'])]
class ReopenController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        ReopenEventCarbonFootprintService $reopenEventCarbonFootprint,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if (!$this->isCsrfTokenValid('reopen_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $reopenEventCarbonFootprint(
                new ReopenEventCarbonFootprintRequest($eventCarbonFootprint->id()),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reabrir el cálculo: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId]);
        }

        $this->addFlash('success', new TranslatableMessage('El cálculo de la huella de carbono se ha reabierto y el informe generado se ha eliminado.'));

        return $this->redirectToRoute('events_carbon_footprint_materials', ['eventId' => $eventId]);
    }
}
