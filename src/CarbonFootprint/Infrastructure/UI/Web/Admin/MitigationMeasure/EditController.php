<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasure;

use App\CarbonFootprint\Application\MitigationMeasure\Update\MitigationMeasureEffectData;
use App\CarbonFootprint\Application\MitigationMeasure\Update\UpdateMitigationMeasureRequest;
use App\CarbonFootprint\Application\MitigationMeasure\Update\UpdateMitigationMeasureService;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateMitigationMeasureType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mitigation-measures/edit/{mitigationMeasureId}', name: 'admin_mitigation_measures_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetMitigationMeasureFromIdTrait;

    public function __invoke(
        string $mitigationMeasureId,
        MitigationMeasureRepository $mitigationMeasureRepository,
        UpdateMitigationMeasureService $updateMitigationMeasure,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $mitigationMeasure = $this->getMitigationMeasureFromId($mitigationMeasureId, $mitigationMeasureRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $mitigationMeasure);

        $effects = [];
        foreach ($mitigationMeasure->effects() as $effect) {
            $effects[] = MitigationMeasureEffectData::fromMitigationMeasureEffect($effect);
        }

        $mitigationMeasureRequest = new UpdateMitigationMeasureRequest(
            mitigationMeasureId: $mitigationMeasure->id(),
            name: $mitigationMeasure->name(),
            description: $mitigationMeasure->description(),
            categoryId: $mitigationMeasure->categoryId(),
            effects: $effects,
        );

        try {
            $form = $this->createForm(UpdateMitigationMeasureType::class, $mitigationMeasureRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateMitigationMeasure, $loggedUser) {
                $updateMitigationMeasure($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Medida actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_mitigation_measures');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/mitigation_measures/edit.html.twig', [
            'mitigationMeasure' => $mitigationMeasure,
            'form' => $form->createView(),
        ]);
    }
}
