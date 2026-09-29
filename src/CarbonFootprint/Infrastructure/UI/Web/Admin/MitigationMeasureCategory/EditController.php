<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasureCategory;

use App\CarbonFootprint\Application\MitigationMeasureCategory\Update\UpdateMitigationMeasureCategoryRequest;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Update\UpdateMitigationMeasureCategoryService;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateMitigationMeasureCategoryType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mitigation-measure-categories/edit/{mitigationMeasureCategoryId}', name: 'admin_mitigation_measure_categories_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMitigationMeasureCategoryFromIdTrait;

    public function __invoke(
        string $mitigationMeasureCategoryId,
        MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
        UpdateMitigationMeasureCategoryService $updateMitigationMeasureCategory,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $mitigationMeasureCategory = $this->getMitigationMeasureCategoryFromId($mitigationMeasureCategoryId, $mitigationMeasureCategoryRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $mitigationMeasureCategory);

        $mitigationMeasureCategoryRequest = new UpdateMitigationMeasureCategoryRequest(
            mitigationMeasureCategoryId: $mitigationMeasureCategory->id(),
            name: $mitigationMeasureCategory->name(),
        );

        try {
            $form = $this->createForm(UpdateMitigationMeasureCategoryType::class, $mitigationMeasureCategoryRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMitigationMeasureCategory, $loggedUser) {
                $updateMitigationMeasureCategory($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Categoría de medidas actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_mitigation_measure_categories');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/mitigation_measure_categories/edit.html.twig', [
            'mitigationMeasureCategory' => $mitigationMeasureCategory,
            'form' => $form->createView(),
        ]);
    }
}
