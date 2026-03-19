<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Security\Application\Action;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Property\Application\Brand\Update\UpdateBrandRequest;
use App\Property\Application\Brand\Update\UpdateBrandService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Property\Infrastructure\Symfony\Form\AdminUpdateBrandType;
use App\Property\Infrastructure\UI\Web\Admin\Brand\GetBrandFromIdTrait;

#[Route('/admin/brands/edit/{brandId}', name: 'admin_brands_edit')]
class EditController extends AbstractController
{
    use EditTrait, GetBrandFromIdTrait;

    public function __invoke(
        string $brandId, 
        BrandRepository $brandRepository, 
        UpdateBrandService $updateBrand, 
        LoggedUser $loggedUser,
        Request $request
    ): Response
    {   
        $brand = $this->getBrandFromId($brandId, $brandRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $brand);

        $brandRequest = new UpdateBrandRequest(
            brandId: $brand->id(),
            name: $brand->name(),
            externalId: $brand->externalId()
        );

        $form = $this->createForm(AdminUpdateBrandType::class, $brandRequest); 

        try {
            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateBrand, $loggedUser): void {
                $brandRequest = $form->getData();

                $updateBrand($brandRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Marca actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_brands');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/admin/brands/edit.html.twig', [
            'brand' => $brand,
            'form' => $form->createView()
        ]);
    }
}
