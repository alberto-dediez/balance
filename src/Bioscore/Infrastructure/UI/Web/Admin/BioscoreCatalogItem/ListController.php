<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem;

use App\Bioscore\Application\BioscoreCatalogItem\List\ListBioscoreCatalogItemsRequest;
use App\Bioscore\Application\BioscoreCatalogItem\List\ListBioscoreCatalogItemsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Infrastructure\Symfony\Form\ListBioscoreCatalogItemsType;
use App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem\Bulk\IgnoreAction;
use App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem\Bulk\UnmapAction;
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

#[Route('/admin/bioscore/facility-fuels', name: 'admin_bioscore_facility_fuels', defaults: ['catalog' => 'facility_fuel'])]
#[Route('/admin/bioscore/transport-fuels', name: 'admin_bioscore_transport_fuels', defaults: ['catalog' => 'transport_fuel'])]
#[Route('/admin/bioscore/refrigerant-gases', name: 'admin_bioscore_refrigerant_gases', defaults: ['catalog' => 'refrigerant_gas'])]
#[Route('/admin/bioscore/electricity-marketers', name: 'admin_bioscore_electricity_marketers', defaults: ['catalog' => 'electricity_marketer'])]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        string $catalog,
        ListBioscoreCatalogItemsService $listItems,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $bioscoreCatalog = BioscoreCatalog::from($catalog);

        try {
            [$filter, $form] = $this->filter(
                $request,
                static fn(): ListBioscoreCatalogItemsRequest => new ListBioscoreCatalogItemsRequest(catalog: $bioscoreCatalog),
                ListBioscoreCatalogItemsType::class,
            );

            assert($filter instanceof ListBioscoreCatalogItemsRequest);

            $filter->catalog = $bioscoreCatalog;

            $response = $listItems($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->items,
            'actions' => [
                new IgnoreAction(),
                new UnmapAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@bioscore/admin/catalogs/list.html.twig', [
            'catalog' => $catalog,
            'bulkForm' => $bulkForm->createView(),
            'items' => $response->items,
            'targetsById' => $response->targetsById,
            'pendingCount' => $response->pendingCount,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
