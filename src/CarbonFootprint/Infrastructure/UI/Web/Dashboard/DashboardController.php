<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Dashboard;

use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\EventEmissions;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\GetOwnerDashboardRequest;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\GetOwnerDashboardService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/', name: 'dashboard', priority: 1)]
class DashboardController extends AbstractController
{
    public function __invoke(
        GetOwnerDashboardService $getOwnerDashboard,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        if (!$this->isGranted(Role::Owner->value)) {
            return $this->render('@shared/home.html.twig');
        }

        $account = $loggedAccount();

        if (null == $account) {
            return $this->render('@shared/home.html.twig');
        }

        try {
            $dashboard = $getOwnerDashboard(new GetOwnerDashboardRequest(accountId: $account->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/dashboard/dashboard.html.twig', [
            'dashboard' => $dashboard,
            'emissionsYearCategories' => array_keys($dashboard->emissionsByYear),
            'emissionsYearValues' => $this->roundValues($dashboard->emissionsByYear),
            'phaseCategories' => $this->translateKeys($dashboard->emissionsByPhase, 'phase', $translator),
            'phaseValues' => $this->roundValues($dashboard->emissionsByPhase),
            'sourceCategories' => $this->translateKeys($dashboard->emissionsBySource, 'source', $translator),
            'sourceValues' => $this->roundValues($dashboard->emissionsBySource),
            'topEventCategories' => array_map(static fn(EventEmissions $eventEmissions): string => $eventEmissions->event->name(), $dashboard->topEventsByEmissions),
            'topEventValues' => array_map(static fn(EventEmissions $eventEmissions): float => round($eventEmissions->co2eq, 2), $dashboard->topEventsByEmissions),
            'typologyCategories' => $this->translateKeys($dashboard->eventsByTypology, 'eventTypologyType', $translator),
            'typologyValues' => array_values($dashboard->eventsByTypology),
        ]);
    }

    /**
     * @param array<int|string, float> $values
     *
     * @return list<float>
     */
    private function roundValues(array $values): array
    {
        return array_map(static fn(float $value): float => round($value, 2), array_values($values));
    }

    /**
     * @param array<string, float|int> $values
     *
     * @return list<string>
     */
    private function translateKeys(array $values, string $domain, TranslatorInterface $translator): array
    {
        return array_map(
            static fn(string $key): string => $translator->trans($key, [], $domain),
            array_keys($values),
        );
    }
}
