<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog;

use App\FoodLoss\Application\DeliveryLog\List\ListDeliveryLogsRequest;
use App\FoodLoss\Application\DeliveryLog\List\ListDeliveryLogsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListDeliveryLogsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/delivery-logs', name: 'delivery_logs')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListDeliveryLogsService $listDeliveryLogs, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, function () use ($loggedAccount): ListDeliveryLogsRequest {
                $request = new ListDeliveryLogsRequest();
                $request->accountId = $loggedAccount()?->id();

                return $request;
            }, ListDeliveryLogsType::class);

            $response = $listDeliveryLogs($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/delivery-logs/list.html.twig', [
            'deliveryLogs' => $response->deliveryLogs,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
