<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\Ignore\IgnoreProviderContributionRequest;
use App\CarbonFootprint\Application\ProviderContribution\Ignore\IgnoreProviderContributionService;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/providers/{providerContributionId}/ignore', name: 'events_carbon_footprint_provider_contribution_ignore', methods: ['GET'])]
class IgnoreController extends AbstractController
{
    public function __invoke(
        string $eventId,
        string $providerContributionId,
        IgnoreProviderContributionService $ignoreProviderContribution,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('provider_contribution_ignore_' . $providerContributionId, $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $ignoreProviderContribution(
                new IgnoreProviderContributionRequest(new ProviderContributionId($providerContributionId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('events_carbon_footprint_providers', ['eventId' => $eventId]);
    }
}
