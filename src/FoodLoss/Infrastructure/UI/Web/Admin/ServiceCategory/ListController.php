<?php

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\FoodLoss\Application\ServiceCategory\List\ListServiceCategoriesRequest;
use App\FoodLoss\Application\ServiceCategory\List\ListServiceCategoriesService;
use App\Security\Application\UnauthorizedActionException;
use App\FoodLoss\Infrastructure\Symfony\Form\ListServiceCategoriesType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory\Bulk\RemoveServiceCategory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/service_categories', name: 'admin_service_categories')]
class ListController extends AbstractController
{
    use FilterTrait, BulkTrait;

    public function __invoke(ListServiceCategoriesService $listServiceCategories, LoggedUser $loggedUser, Request $request): Response
    {   

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListServiceCategoriesRequest();
            }, ListServiceCategoriesType::class);

            $response = $listServiceCategories($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest, [
            'elements' => $response->serviceCategories,
            'actions' => [
                new RemoveServiceCategory()
            ]
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/admin/service_categories/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'serviceCategories' => $response->serviceCategories,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
