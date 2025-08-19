<?php

namespace App\Waste\Infrastructure\UI\Web\DailyWaste;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Infrastructure\Symfony\Form\StatisticsDailyWasteType;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteService;

#[Route('/daily-wastes/statistics', name: 'daily_wastes_statistics')]
class StatisticsController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        StatisticsDailyWasteService $statisticsDailyWastes, 
        Request $request
    ): Response
    {   
        $account = $loggedAccount();
        $user = $loggedUser();

        list($filter, $form) = $this->filter($request, function () use ($account) {
            return  new StatisticsDailyWasteRequest(
                dayFrom: new \DateTimeImmutable("NOW -1 MONTH"),
                dayTo: new \DateTimeImmutable("NOW"),
                accountId: $account?->id()
            );
        }, StatisticsDailyWasteType::class);

        try {
            $response = $statisticsDailyWastes($filter, $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/daily-wastes/statistics.html.twig', [
            'statistics' => $response,
            'form' => $form->createView()
        ]);
    }
}
