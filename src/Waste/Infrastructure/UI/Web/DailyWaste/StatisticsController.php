<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\DailyWaste;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteRequest;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteService;
use App\Waste\Infrastructure\Symfony\Form\StatisticsDailyWasteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/daily-wastes/statistics', name: 'daily_wastes_statistics')]
class StatisticsController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedAccount $loggedAccount,
        LoggedUser $loggedUser,
        StatisticsDailyWasteService $statisticsDailyWastes,
        Request $request,
    ): Response {
        $account = $loggedAccount();
        $user = $loggedUser();

        [$filter, $form] = $this->filter($request, fn() => new StatisticsDailyWasteRequest(
            accountId: $account?->id(),
            dayFrom: (new \DateTimeImmutable('NOW -11 MONTHS'))->modify('first day of this month'),
            dayTo: new \DateTimeImmutable('NOW'),
        ), StatisticsDailyWasteType::class);

        try {
            $response = $statisticsDailyWastes($filter, $user);
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@waste/daily-wastes/statistics.html.twig', [
            'statistics' => $response,
            'form' => $form->createView(),
        ]);
    }
}
