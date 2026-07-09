<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\Ignore\UnignoreProviderContributionRequest;
use App\CarbonFootprint\Application\ProviderContribution\Ignore\UnignoreProviderContributionService;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/providers/{providerContributionId}/unignore', name: 'events_carbon_footprint_provider_contribution_unignore', methods: ['GET'])]
class UnignoreController extends AbstractController
{
    public function __invoke(
        string $eventId,
        string $providerContributionId,
        UnignoreProviderContributionService $unignoreProviderContribution,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('provider_contribution_unignore_' . $providerContributionId, $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $unignoreProviderContribution(
                new UnignoreProviderContributionRequest(new ProviderContributionId($providerContributionId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('events_carbon_footprint_providers', ['eventId' => $eventId]);
    }
}
