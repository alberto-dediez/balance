<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory;

use App\FoodLoss\Application\ServiceCategory\Register\RegisterServiceCategoryService;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterServiceCategoryType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/service-categories/register', name: 'admin_service_categories_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterServiceCategoryService $registerServiceCategory, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Action::Register->value, new ServiceCategory(ServiceCategoryId::generate()));

        $form = $this->createForm(RegisterServiceCategoryType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerServiceCategory, $loggedUser): true {
                $registerRequest = $form->getData();

                $response = $registerServiceCategory($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Categoría de servicio creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_service_categories');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/service-categories/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
