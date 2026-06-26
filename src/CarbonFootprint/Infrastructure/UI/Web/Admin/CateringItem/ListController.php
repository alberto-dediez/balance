<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem;

use App\CarbonFootprint\Application\CateringItem\List\ListCateringItemsRequest;
use App\CarbonFootprint\Application\CateringItem\List\ListCateringItemsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListCateringItemsType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem\Bulk\RemoveAction;
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

#[Route('/admin/catering-items', name: 'admin_catering_items')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListCateringItemsService $listCateringItems, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListCateringItemsRequest();
            }, ListCateringItemsType::class);

            $response = $listCateringItems($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->cateringItems,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/catering_items/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'cateringItems' => $response->cateringItems,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
