<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Waste\Application\Waste\List\ListWastesRequest;
use App\Waste\Application\Waste\List\ListWastesService;
use App\Security\Application\UnauthorizedActionException;
use App\Waste\Infrastructure\Symfony\Form\ListWastesType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Waste\Infrastructure\UI\Web\Admin\Waste\Bulk\RemoveWaste;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/wastes', name: 'admin_wastes')]
class ListController extends AbstractController
{
    use FilterTrait, BulkTrait;

    public function __invoke(ListWastesService $listWastes, LoggedUser $loggedUser, Request $request): Response
    {   

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListWastesRequest();
            }, ListWastesType::class);

            $response = $listWastes($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest, [
            'elements' => $response->wastes,
            'actions' => [
                new RemoveWaste()
            ]
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@waste/admin/wastes/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'wastes' => $response->wastes,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
