<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem;

use App\CarbonFootprint\Application\MerchandisingItem\List\ListMerchandisingItemsRequest;
use App\CarbonFootprint\Application\MerchandisingItem\List\ListMerchandisingItemsService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListMerchandisingItemsType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem\Bulk\RemoveAction;
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

#[Route('/admin/merchandising-items', name: 'admin_merchandising_items')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListMerchandisingItemsService $listMerchandisingItems, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListMerchandisingItemsRequest();
            }, ListMerchandisingItemsType::class);

            $response = $listMerchandisingItems($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->merchandisingItems,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/merchandising_items/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'merchandisingItems' => $response->merchandisingItems,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
