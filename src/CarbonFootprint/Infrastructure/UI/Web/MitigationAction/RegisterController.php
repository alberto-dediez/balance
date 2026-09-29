<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\MitigationAction;

use App\CarbonFootprint\Application\MitigationAction\MitigationPlanFootprints;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Application\MitigationAction\Register\RegisterMitigationActionRequest;
use App\CarbonFootprint\Application\MitigationAction\Register\RegisterMitigationActionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterMitigationActionType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/mitigation-actions/{subjectType}/{carbonFootprintId}/register', name: 'mitigation_actions_register', requirements: ['subjectType' => 'property|company'])]
class RegisterController extends AbstractController
{
    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        MitigationPlanFootprints $footprints,
        RegisterMitigationActionService $registerMitigationAction,
        MitigationPlanStepRoute $stepRoute,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);

        try {
            $carbonFootprint = $footprints->find($type, $carbonFootprintId);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $carbonFootprint);

        $step = $stepRoute->resolve($type, $carbonFootprintId);

        if ($carbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute($step['route'], $step['params']);
        }

        $form = $this->createForm(RegisterMitigationActionType::class, null, ['year' => $carbonFootprint->calculationYear()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{measure: ?MitigationMeasureId, name: string, description: string, targetYear: int} $data */
            $data = $form->getData();

            try {
                $response = $registerMitigationAction(
                    new RegisterMitigationActionRequest(
                        subjectType: $type,
                        carbonFootprintId: $carbonFootprintId,
                        measureId: $data['measure'],
                        name: $data['name'],
                        description: $data['description'],
                        targetYear: $data['targetYear'],
                    ),
                    $loggedUser(),
                );

                $this->addFlash('success', new TranslatableMessage('Medida añadida al plan: {name}', ['{name}' => $response->name]));

                return $this->redirectToRoute('mitigation_actions_edit', ['mitigationActionId' => $response->mitigationActionId]);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (MitigationPlanNotEditableException) {
                $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute($step['route'], $step['params']);
            } catch (\DomainException|\InvalidArgumentException) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido añadir la medida al plan'));
            }
        }

        return $this->render('@carbon_footprint/mitigation_actions/register.html.twig', [
            'form' => $form->createView(),
            'carbonFootprint' => $carbonFootprint,
            'subjectType' => $subjectType,
            'stepPath' => $this->generateUrl($step['route'], $step['params']),
        ]);
    }
}
