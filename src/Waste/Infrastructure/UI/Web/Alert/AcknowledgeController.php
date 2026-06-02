<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Alert;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Alert\Acknowledge\AcknowledgeAlertRequest;
use App\Waste\Application\Alert\Acknowledge\AcknowledgeAlertService;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/alerts/acknowledge/{alertId}', name: 'alerts_acknowledge')]
class AcknowledgeController extends AbstractController
{
    public function __invoke(
        string $alertId,
        AlertRepository $alertRepository,
        AcknowledgeAlertService $acknowledgeAlert,
        LoggedUser $loggedUser,
    ): Response {
        $alert = $alertRepository->find(new AlertId($alertId));

        if (null === $alert) {
            throw $this->createNotFoundException('Alerta no encontrada');
        }

        try {
            $acknowledgeAlert(new AcknowledgeAlertRequest($alert->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Alerta marcada como leída'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('alerts');
    }
}
