<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Category;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Category\Update\UpdateCategoryRequest;
use App\Property\Application\Category\Update\UpdateCategoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminUpdateCategoryType;
use App\Property\Infrastructure\UI\Web\Admin\Category\GetCategoryFromIdTrait;

#[Route('/admin/categories/edit/{categoryId}', name: 'admin_categories_edit')]
class EditController extends AbstractController
{
    use EditTrait, GetCategoryFromIdTrait;

    public function __invoke(
        string $categoryId, 
        CategoryRepository $categoryRepository, 
        UpdateCategoryService $updateCategory, 
        LoggedUser $loggedUser,
        Request $request
    ): Response
    {   
        $category = $this->getCategoryFromId($categoryId, $categoryRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $category);

        $categoryRequest = new UpdateCategoryRequest(
            categoryId: $category->id(),
            name: $category->name(),
            externalId: $category->externalId()
        );

        $form = $this->createForm(AdminUpdateCategoryType::class, $categoryRequest); 

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCategory, $loggedUser): void {
                $categoryRequest = $form->getData();

                $updateCategory($categoryRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Categoría actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_categories');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/categories/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView()
        ]);
    }
}
