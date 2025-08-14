<?php

namespace App\Waste\Infrastructure\UI\Web\DailyWaste;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Application\DailyWaste\View\ViewDailyWasteRequest;
use App\Waste\Application\DailyWaste\View\ViewDailyWasteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/daily-wastes/view/{dailyWasteId}', name: 'daily_wastes_view')]
class ViewController extends AbstractController
{
    public function __invoke(
        $dailyWasteId,
        LoggedUser $loggedUser,
        ViewDailyWasteService $viewDailyWaste, 
        Request $request
    ): Response
    {   
        $user = $loggedUser();

        try {
            $response = $viewDailyWaste(new ViewDailyWasteRequest(
                dailyWasteId: new DailyWasteId($dailyWasteId)
            ), $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/daily-wastes/view.html.twig', [
            'dailyWaste' => $response->dailyWaste,
            'measurements' => $response->measurements
        ]);
    }
}
