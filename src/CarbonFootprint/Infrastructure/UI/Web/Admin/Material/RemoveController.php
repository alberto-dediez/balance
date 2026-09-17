<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Material;

use App\CarbonFootprint\Application\Material\Remove\RemoveMaterialRequest;
use App\CarbonFootprint\Application\Material\Remove\RemoveMaterialService;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materials/remove/{materialId}', name: 'admin_materials_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetMaterialFromIdTrait;

    public function __invoke(
        string $materialId,
        MaterialRepository $materialRepository,
        RemoveMaterialService $removeMaterial,
        LoggedUser $loggedUser,
    ): Response {
        $material = $this->getMaterialFromId($materialId, $materialRepository);

        try {
            $removeMaterial(new RemoveMaterialRequest($material->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el material {material}', ['{material}' => $material->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el material {material}', ['{material}' => $material->name()]));
        }

        return $this->redirectToRoute('admin_materials');
    }
}
