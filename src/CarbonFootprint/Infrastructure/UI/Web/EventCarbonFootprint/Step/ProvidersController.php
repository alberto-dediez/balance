<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\ProviderContribution\AssignProviders\AssignedProvider;
use App\CarbonFootprint\Application\ProviderContribution\AssignProviders\AssignProvidersRequest;
use App\CarbonFootprint\Application\ProviderContribution\AssignProviders\AssignProvidersService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ProvidersStepView;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/step/providers', name: 'events_carbon_footprint_providers')]
class ProvidersController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        AssignProvidersService $assignProviders,
        ProvidersStepView $stepView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if ($eventCarbonFootprint->status()->isCompleted()) {
            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId, 'step' => 'providers']);
        }

        if (!$eventCarbonFootprint->status()->isBreakdownAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_breakdown', ['eventId' => $eventId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('providers_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $providerIds = $request->request->all('providers')['providerIds'] ?? [];

            $assignedProviders = [];
            foreach ($providerIds as $providerId) {
                if (!is_string($providerId) || '' === $providerId) {
                    continue;
                }

                $assignedProviders[] = new AssignedProvider(new ProviderId($providerId));
            }

            try {
                $assignProviders(
                    new AssignProvidersRequest($eventCarbonFootprint->id(), $assignedProviders),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se han podido asignar los proveedores: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

                return $this->redirectToRoute('events_carbon_footprint_providers', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_employees', ['eventId' => $eventId]);
        }

        return $this->render(
            '@carbon_footprint/event_carbon_footprints/step/providers.html.twig',
            $stepView->build($eventCarbonFootprint, $loggedUser()) + ['event' => $event, 'eventId' => $eventId],
        );
    }
}
