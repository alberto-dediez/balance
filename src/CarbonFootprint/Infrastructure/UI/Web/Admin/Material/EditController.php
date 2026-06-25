<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Material;

use App\CarbonFootprint\Application\Material\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Material\Update\UpdateMaterialRequest;
use App\CarbonFootprint\Application\Material\Update\UpdateMaterialService;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateMaterialType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materials/edit/{materialId}', name: 'admin_materials_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMaterialFromIdTrait;

    public function __invoke(
        string $materialId,
        MaterialRepository $materialRepository,
        UpdateMaterialService $updateMaterial,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $material = $this->getMaterialFromId($materialId, $materialRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $material);

        $emissionFactors = [];
        foreach ($material->emissionFactors() as $materialEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($materialEmissionFactor->emissionFactor());
        }

        $materialRequest = new UpdateMaterialRequest(
            materialId: $material->id(),
            name: $material->name(),
            category: $material->category(),
            unit: $material->unit(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateMaterialType::class, $materialRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMaterial, $loggedUser) {
                $materialRequest = $form->getData();

                $updateMaterial($materialRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Material actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_materials');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/materials/edit.html.twig', [
            'material' => $material,
            'form' => $form->createView(),
        ]);
    }
}
