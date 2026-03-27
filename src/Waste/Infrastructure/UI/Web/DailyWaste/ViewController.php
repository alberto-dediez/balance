<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\DailyWaste;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\DailyWaste\View\ViewDailyWasteRequest;
use App\Waste\Application\DailyWaste\View\ViewDailyWasteService;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/daily-wastes/view/{dailyWasteId}', name: 'daily_wastes_view')]
class ViewController extends AbstractController
{
    public function __invoke(
        string $dailyWasteId,
        LoggedUser $loggedUser,
        ViewDailyWasteService $viewDailyWaste,
        Request $request,
    ): Response {
        $user = $loggedUser();

        try {
            $response = $viewDailyWaste(new ViewDailyWasteRequest(
                dailyWasteId: new DailyWasteId($dailyWasteId),
            ), $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/daily-wastes/view.html.twig', [
            'dailyWaste' => $response->dailyWaste,
            'measurements' => $response->measurements,
        ]);
    }
}
