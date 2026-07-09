<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\Resend\ResendProviderContributionNotificationRequest;
use App\CarbonFootprint\Application\ProviderContribution\Resend\ResendProviderContributionNotificationService;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/providers/{providerContributionId}/resend', name: 'events_carbon_footprint_provider_contribution_resend', methods: ['GET'])]
class ResendController extends AbstractController
{
    public function __invoke(
        string $eventId,
        string $providerContributionId,
        ResendProviderContributionNotificationService $resendNotification,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('provider_contribution_resend_' . $providerContributionId, $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        try {
            $resendNotification(
                new ResendProviderContributionNotificationRequest(new ProviderContributionId($providerContributionId)),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reenviar la notificación: {error}', ['{error}' => $e->getMessage()]));
        }

        return $this->redirectToRoute('events_carbon_footprint_providers', ['eventId' => $eventId]);
    }
}
