<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\SurplusCause;

use App\FoodLoss\Application\SurplusCause\List\ListSurplusCausesRequest;
use App\FoodLoss\Application\SurplusCause\List\ListSurplusCausesService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListSurplusCausesType;
use App\FoodLoss\Infrastructure\UI\Web\Admin\SurplusCause\Bulk\RemoveAction;
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

#[Route('/admin/surplus-causes', name: 'admin_surplus_causes')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListSurplusCausesService $listSurplusCauses, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn() => new ListSurplusCausesRequest(), ListSurplusCausesType::class);

            $response = $listSurplusCauses($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->surplusCauses,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/admin/surplus-causes/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'surplusCauses' => $response->surplusCauses,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
