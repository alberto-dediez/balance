<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Category;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Category\List\ListCategoriesRequest;
use App\Property\Application\Category\List\ListCategoriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminListCategoriesType;
use App\Property\Infrastructure\UI\Web\Admin\Category\Bulk\RemoveCategory;

#[Route('/admin/categories', name: 'admin_categories')]
class ListController extends AbstractController
{
    use FilterTrait, BulkTrait;

    public function __invoke(ListCategoriesService $listCategories, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::List->value, new Category(CategoryId::generate()));

        try {
            [$filter, $form] = $this->filter($request, fn() => new ListCategoriesRequest(), AdminListCategoriesType::class);

            $response = $listCategories($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest, [
            'elements' => $response->categories,
            'actions' => [
                new RemoveCategory()
            ]
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@property/admin/categories/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'categories' => $response->categories,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
