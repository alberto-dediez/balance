<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Alert;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\Alert\View\ViewAlertRequest;
use App\Waste\Application\Alert\View\ViewAlertService;
use App\Waste\Domain\Model\Alert\AlertId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/alerts/view/{alertId}', name: 'alerts_view')]
class ViewController extends AbstractController
{
    public function __invoke(
        string $alertId,
        LoggedUser $loggedUser,
        ViewAlertService $viewAlert,
    ): Response {
        try {
            $response = $viewAlert(new ViewAlertRequest(
                alertId: new AlertId($alertId),
            ), $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/alerts/view.html.twig', [
            'alert' => $response->alert,
        ]);
    }
}
