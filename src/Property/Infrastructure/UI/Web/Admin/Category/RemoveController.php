<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Category;

use App\Property\Application\Category\Remove\RemoveCategoryRequest;
use App\Property\Application\Category\Remove\RemoveCategoryService;
use App\Property\Domain\Model\Category\Category;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/categories/remove/{categoryId}', name: 'admin_categories_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetCategoryFromIdTrait;

    public function __invoke(
        string $categoryId,
        CategoryRepository $categoryRepository,
        RemoveCategoryService $removeCategory,
        LoggedUser $loggedUser,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Admin->value, new Category(CategoryId::generate()));

        $category = $this->getCategoryFromId($categoryId, $categoryRepository);

        try {
            $removeCategory(new RemoveCategoryRequest($category->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la categoría {category}', ['{category}' => $category->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la categoría {category}', ['{category}' => $category->name()]));
        }

        return $this->redirectToRoute('admin_categories');
    }
}
