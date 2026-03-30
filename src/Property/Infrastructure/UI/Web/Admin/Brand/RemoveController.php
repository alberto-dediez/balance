<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Property\Application\Brand\Remove\RemoveBrandRequest;
use App\Property\Application\Brand\Remove\RemoveBrandService;
use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/brands/remove/{brandId}', name: 'admin_brands_remove')]
class RemoveController extends AbstractController
{
    use GetBrandFromIdTrait;

    public function __invoke(
        string $brandId,
        BrandRepository $brandRepository,
        RemoveBrandService $removeBrand,
        LoggedUser $loggedUser,
    ): Response {
        $this->denyAccessUnlessGranted(Action::Admin->value, new Brand(BrandId::generate()));

        $brand = $this->getBrandFromId($brandId, $brandRepository);

        try {
            $removeBrand(new RemoveBrandRequest($brand->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la marca {brand}', ['{brand}' => $brand->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la marca {brand}', ['{brand}' => $brand->name()]));
        }

        return $this->redirectToRoute('admin_brands');
    }
}
