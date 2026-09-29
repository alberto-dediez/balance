<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySector;

use App\Materiality\Application\MaterialitySector\List\ListMaterialitySectorsRequest;
use App\Materiality\Application\MaterialitySector\List\ListMaterialitySectorsService;
use App\Materiality\Infrastructure\Symfony\Form\ListMaterialitySectorsType;
use App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySector\Bulk\RemoveAction;
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

#[Route('/admin/materiality-sectors', name: 'admin_materiality_sectors')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListMaterialitySectorsService $listMaterialitySectors, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListMaterialitySectorsRequest();
            }, ListMaterialitySectorsType::class);

            $response = $listMaterialitySectors($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->materialitySectors,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@materiality/admin/materiality-sectors/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'materialitySectors' => $response->materialitySectors,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
