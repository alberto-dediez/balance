<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory;

use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryRequest;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryService;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateServiceCategoryType;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/service-categories/edit/{serviceCategoryId}', name: 'admin_service_categories_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetServiceCategoryFromIdTrait;

    public function __invoke(
        string $serviceCategoryId,
        ServiceCategoryRepository $serviceCategoryRepository,
        UpdateServiceCategoryService $updateServiceCategory,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $serviceCategory = $this->getServiceCategoryFromId($serviceCategoryId, $serviceCategoryRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $serviceCategory);

        $serviceCategoryRequest = new UpdateServiceCategoryRequest(
            serviceCategoryId: $serviceCategory->id(),
            name: $serviceCategory->name(),
        );

        try {
            $form = $this->createForm(UpdateServiceCategoryType::class, $serviceCategoryRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateServiceCategory, $loggedUser) {
                $serviceCategoryRequest = $form->getData();

                $updateServiceCategory($serviceCategoryRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Categoría de servicio actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_service_categories');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/admin/service-categories/edit.html.twig', [
            'serviceCategory' => $serviceCategory,
            'form' => $form->createView(),
        ]);
    }
}
