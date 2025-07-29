<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Category;

use App\Security\Application\Action;
use App\Property\Domain\Model\Category\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Category\List\ListCategoriesRequest;
use App\Property\Application\Category\List\ListCategoriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminListCategoriesType;

#[Route('/admin/categories', name: 'admin_categories')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListCategoriesService $listCategories, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::List->value, new Category(CategoryId::generate()));

        try {
            list($filter, $form) = $this->filter($request, function () {
                return  new ListCategoriesRequest();
            }, AdminListCategoriesType::class);

            $response = $listCategories($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/categories/list.html.twig', [
            'categories' => $response->categories,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
