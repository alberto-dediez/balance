<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Category;

use App\Security\Application\Action;
use App\Property\Domain\Model\Category\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Property\Domain\Model\Category\CategoryId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Application\Category\Register\RegisterCategoryService;
use App\Property\Infrastructure\Symfony\Form\AdminRegisterCategoryType;

#[Route('/admin/categories/register', name: 'admin_categories_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterCategoryService $registerCategory, LoggedUser $loggedUser, Request $request): Response
    {   
        $this->denyAccessUnlessGranted(Action::Register->value, new Category(CategoryId::generate()));

        $form = $this->createForm(AdminRegisterCategoryType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCategory, $loggedUser): true {
                $registerRequest = $form->getData();

                $response = $registerCategory($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage("Categoría creada correctamente: {name}", ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_categories');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/categories/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
