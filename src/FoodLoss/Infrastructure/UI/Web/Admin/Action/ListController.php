<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Action;

use App\FoodLoss\Application\Action\List\ListActionsRequest;
use App\FoodLoss\Application\Action\List\ListActionsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListActionsType;
use App\FoodLoss\Infrastructure\UI\Web\Admin\Action\Bulk\RemoveAction;
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

#[Route('/admin/actions', name: 'admin_actions')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListActionsService $listActions, LoggedUser $loggedUser, Request $request): Response
    {

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListActionsRequest();
            }, ListActionsType::class);

            $response = $listActions($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->actions,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/admin/actions/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'actions' => $response->actions,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
