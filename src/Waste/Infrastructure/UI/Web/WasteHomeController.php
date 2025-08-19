<?php

namespace App\Waste\Infrastructure\UI\Web;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteRequest;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;
use App\Waste\Application\Measurement\List\ListMeasurementsService;

#[Route('/', name: 'waste_home', priority: 5)]
class WasteHomeController extends AbstractController
{
    public function __invoke(
        ListMeasurementsService $listService,
        StatisticsDailyWasteService $statisticsService,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount
    ): Response
    {   
        $user = $loggedUser();
        $account = $loggedAccount();

        if (null === $account) {
            return $this->render('@shared/home.html.twig', []);
        }

        $userId = $this->isGranted(Role::Owner->value)
            ? null
            : $user->id()
        ;

        $result = $listService(new ListMeasurementsRequest(
            userId: $userId,
            accountId: $account?->id(),
            limit: 9
        ), $user);

        if ($this->isGranted(Role::Owner->value) && null !== $account) {
            $statistics = $statisticsService(new StatisticsDailyWasteRequest(
                accountId: $account?->id(),
                dayFrom: new \DateTimeImmutable("NOW -1 MONTH"),
                dayTo: new \DateTimeImmutable("NOW"),
            ), $user);

            return $this->render('@waste/home/owner.html.twig', [
                'measurements' => $result->measurements,
                'statistics' => $statistics
            ]);
        }

        return $this->render('@waste/home/default.html.twig', [
            'measurements' => $result->measurements
        ]);
    }
}
