<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Material;

use App\CarbonFootprint\Application\Material\Register\RegisterMaterialService;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterMaterialType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materials/register', name: 'admin_materials_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterMaterialService $registerMaterial, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Material(MaterialId::generate()));

        $form = $this->createForm(RegisterMaterialType::class);

        try {
            $materialId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMaterial, $loggedUser, &$materialId) {
                $registerRequest = $form->getData();

                $response = $registerMaterial($registerRequest, $loggedUser());

                $materialId = $response->materialId;

                $this->addFlash('success', new TranslatableMessage('Material creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($materialId instanceof MaterialId) {
                    return $this->redirectToRoute('admin_materials_edit', ['materialId' => $materialId]);
                }

                return $this->redirectToRoute('admin_materials');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/materials/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
