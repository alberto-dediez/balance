<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Application\DeliveryLog\Dashboard\GetDonationDashboardRequest;
use App\FoodLoss\Application\DeliveryLog\Dashboard\GetDonationDashboardService;
use App\FoodLoss\Infrastructure\Symfony\Form\DashboardDeliveryLogsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/donations/dashboard', name: 'donations_dashboard')]
#[IsGranted('ROLE_OWNER')]
class DashboardController extends AbstractController
{
    use FilterTrait;

    public function __invoke(GetDonationDashboardService $getDonationDashboard, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): GetDonationDashboardRequest {
                $request = new GetDonationDashboardRequest(limit: 0);
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, DashboardDeliveryLogsType::class);

            $response = $getDonationDashboard($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/delivery-logs/dashboard.html.twig', [
            'dashboard' => $response,
            'form' => $form->createView(),
        ]);
    }
}
