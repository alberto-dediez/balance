<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RefrigerantGas;

use App\CarbonFootprint\Application\RefrigerantGas\List\ListRefrigerantGasesRequest;
use App\CarbonFootprint\Application\RefrigerantGas\List\ListRefrigerantGasesService;
use App\CarbonFootprint\Infrastructure\Symfony\Form\ListRefrigerantGasesType;
use App\CarbonFootprint\Infrastructure\UI\Web\Admin\RefrigerantGas\Bulk\RemoveAction;
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

#[Route('/admin/refrigerant-gases', name: 'admin_refrigerant_gases')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListRefrigerantGasesService $listRefrigerantGases, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            list($filter, $form) = $this->filter($request, function () {
                return new ListRefrigerantGasesRequest();
            }, ListRefrigerantGasesType::class);

            $response = $listRefrigerantGases($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->refrigerantGases,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@carbon_footprint/admin/refrigerant_gases/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'refrigerantGases' => $response->refrigerantGases,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
