<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySettings;

use App\Materiality\Application\MaterialitySettings\Get\GetMaterialitySettingsRequest;
use App\Materiality\Application\MaterialitySettings\Get\GetMaterialitySettingsService;
use App\Materiality\Application\MaterialitySettings\Update\UpdateMaterialitySettingsRequest;
use App\Materiality\Application\MaterialitySettings\Update\UpdateMaterialitySettingsService;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;
use App\Materiality\Infrastructure\Symfony\Form\UpdateMaterialitySettingsType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/materiality-settings', name: 'admin_materiality_settings')]
class EditController extends AbstractController
{
    public function __invoke(
        GetMaterialitySettingsService $getMaterialitySettings,
        UpdateMaterialitySettingsService $updateMaterialitySettings,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, new MaterialitySettings(MaterialitySettingsId::generate()));

        try {
            $settings = $getMaterialitySettings(new GetMaterialitySettingsRequest(), $loggedUser());

            $form = $this->createForm(UpdateMaterialitySettingsType::class, new UpdateMaterialitySettingsRequest(
                relevanceThresholdPercent: $settings->relevanceThresholdPercent,
                highMagnitudeForcesInclusion: $settings->highMagnitudeForcesInclusion,
            ));

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $updateMaterialitySettings($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Configuración de materialidad guardada'));

                return $this->redirectToRoute('admin_materiality_settings');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@materiality/admin/materiality-settings/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
