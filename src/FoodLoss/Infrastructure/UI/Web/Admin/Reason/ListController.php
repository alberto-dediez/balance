<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Reason;

use App\FoodLoss\Application\Reason\List\ListReasonsRequest;
use App\FoodLoss\Application\Reason\List\ListReasonsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListReasonsType;
use App\FoodLoss\Infrastructure\UI\Web\Admin\Reason\Bulk\RemoveAction;
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

#[Route('/admin/reasons', name: 'admin_reasons')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListReasonsService $listReasons, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListReasonsRequest();
            }, ListReasonsType::class);

            $response = $listReasons($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->reasons,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/admin/reasons/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'reasons' => $response->reasons,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
