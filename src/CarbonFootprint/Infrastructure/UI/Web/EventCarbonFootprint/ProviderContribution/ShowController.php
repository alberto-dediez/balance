<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\Show\GetProviderContributionDetailRequest;
use App\CarbonFootprint\Application\ProviderContribution\Show\GetProviderContributionDetailService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\CarbonFootprint\Infrastructure\UI\Web\ProviderContribution\View\ProviderContributionPhaseStepView;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/providers/{providerContributionId}', name: 'events_carbon_footprint_provider_contribution_show', methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(
        string $eventId,
        string $providerContributionId,
        GetProviderContributionDetailService $getProviderContributionDetail,
        ProviderContributionPhaseStepView $stepView,
        EventRepository $eventRepository,
        LoggedUser $loggedUser,
    ): Response {
        try {
            $response = $getProviderContributionDetail(
                new GetProviderContributionDetailRequest(new ProviderContributionId($providerContributionId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Contribución de proveedor no encontrada');
        }

        $viewByPhase = [];
        foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $viewByPhase[$phase->value] = $stepView->build($response->sectionsByPhase[$phase->value] ?? []);
        }

        return $this->render('@carbon_footprint/event_carbon_footprints/provider_contribution/show.html.twig', [
            'eventId' => $eventId,
            'event' => $eventRepository->find(new EventId($eventId)),
            'providerContribution' => $response->providerContribution,
            'viewByPhase' => $viewByPhase,
        ]);
    }
}
