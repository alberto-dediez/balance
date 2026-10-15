<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\PurchaseService;

use App\CarbonFootprint\Application\PurchaseService\List\ListPurchaseServicesRequest;
use App\CarbonFootprint\Application\PurchaseService\List\ListPurchaseServicesService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListPurchaseServicesType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\PurchaseService\Bulk\RemoveAction;
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

#[Route('/admin/purchase-services', name: 'admin_purchase_services')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListPurchaseServicesService $listPurchaseServices, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListPurchaseServicesRequest();
            }, ListPurchaseServicesType::class);

            $response = $listPurchaseServices($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->purchaseServices,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/purchase_services/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'purchaseServices' => $response->purchaseServices,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
