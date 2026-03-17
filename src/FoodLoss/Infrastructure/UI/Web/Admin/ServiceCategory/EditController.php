<?php

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryRequest;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryService;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateServiceCategoryType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory\GetServiceCategoryFromIdTrait;

#[Route('/admin/service-categories/edit/{serviceCategoryId}', name: 'admin_service_categories_edit')]
class EditController extends AbstractController
{
    use EditTrait, GetServiceCategoryFromIdTrait;

    public function __invoke(
        string $serviceCategoryId, 
        ServiceCategoryRepository $serviceCategoryRepository, 
        UpdateServiceCategoryService $updateServiceCategory, 
        LoggedUser $loggedUser,
        Request $request
    ): Response
    {   
        $serviceCategory = $this->getServiceCategoryFromId($serviceCategoryId, $serviceCategoryRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $serviceCategory);

        $serviceCategoryRequest = new UpdateServiceCategoryRequest(
            serviceCategoryId: $serviceCategory->id(),
            name: $serviceCategory->name()
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

        return $this->render('@food_loss/admin/service_categories/edit.html.twig', [
            'serviceCategory' => $serviceCategory,
            'form' => $form->createView()
        ]);
    }
}
