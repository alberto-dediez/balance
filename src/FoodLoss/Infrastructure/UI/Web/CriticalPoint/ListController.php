<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\CriticalPoint;

use App\FoodLoss\Application\CriticalPoint\List\ListCriticalPointsRequest;
use App\FoodLoss\Application\CriticalPoint\List\ListCriticalPointsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListCriticalPointsType;
use App\FoodLoss\Infrastructure\UI\Web\CriticalPoint\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/critical-points', name: 'critical_points')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListCriticalPointsService $listCriticalPoints, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListCriticalPointsRequest();
            }, ListCriticalPointsType::class);

            $response = $listCriticalPoints($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->criticalPoints,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/critical-points/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'criticalPoints' => $response->criticalPoints,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
