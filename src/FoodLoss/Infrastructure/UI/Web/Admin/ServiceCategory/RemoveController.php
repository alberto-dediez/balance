<?php

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\UnauthorizedActionException;
use App\FoodLoss\Application\ServiceCategory\Remove\RemoveServiceCategoryRequest;
use App\FoodLoss\Application\ServiceCategory\Remove\RemoveServiceCategoryService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/service-categories/remove/{serviceCategoryId}', name: 'admin_service_categories_remove')]
class RemoveController extends AbstractController
{
    use GetServiceCategoryFromIdTrait;

    public function __invoke(
        string $serviceCategoryId, 
        ServiceCategoryRepository $serviceCategoryRepository,
        RemoveServiceCategoryService $removeServiceCategory,
        LoggedUser $loggedUser
    ): Response
    {   
        $serviceCategory = $this->getServiceCategoryFromId($serviceCategoryId, $serviceCategoryRepository);

        try {
            $removeServiceCategory(new RemoveServiceCategoryRequest($serviceCategory->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage("Se ha eliminado la categoría de servicio {serviceCategory}", ['{serviceCategory}' => $serviceCategory->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage("No se ha podido eliminar la categoría de servicio {serviceCategory}", ['{serviceCategory}' => $serviceCategory->name()]));
        }

        return $this->redirectToRoute('admin_service_categories');
    }
}
