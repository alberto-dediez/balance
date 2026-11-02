<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment\Step;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality\AnswerPropertyCarbonFootprintMaterialityRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality\AnswerPropertyCarbonFootprintMaterialityService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepRequest;
use App\Materiality\Application\MaterialityAssessment\GetStep\GetMaterialityStepService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Materiality\Infrastructure\Symfony\Form\StartMaterialityAssessmentType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/step/materiality', name: 'property_carbon_footprints_materiality', methods: ['GET', 'POST'])]
class PropertyMaterialityStepController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        AnswerPropertyCarbonFootprintMaterialityService $answerMateriality,
        GetMaterialityStepService $getMaterialityStep,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isScope2Answered()) {
            return $this->redirectToRoute(
                $propertyCarbonFootprint->status()->isScope1Answered() ? 'property_carbon_footprints_scope_2' : 'property_carbon_footprints_scope_1',
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $propertyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('property_carbon_footprints_materiality', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('materiality_skip_' . $propertyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerMateriality(new AnswerPropertyCarbonFootprintMaterialityRequest($propertyCarbonFootprint->id()), $loggedUser());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            return $this->redirectToRoute('property_carbon_footprints_scope_3', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        try {
            $step = $getMaterialityStep(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Property, $propertyCarbonFootprintId), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $readOnly = $propertyCarbonFootprint->isCompleted();

        $startForm = null === $step->assessment && !$readOnly
            ? $this->createForm(StartMaterialityAssessmentType::class, null, [
                'action' => $this->generateUrl('materiality_assessments_start', ['subjectType' => MaterialityAssessmentSubjectType::Property->value, 'carbonFootprintId' => $propertyCarbonFootprintId]),
                'previous_year' => $step->previousYear,
                'default_sector_id' => $step->previousSectorId,
                'sectors_available' => [] !== $step->sectors,
            ])
            : null;

        return $this->render(
            '@materiality/assessment/step/property.html.twig',
            [
                'propertyCarbonFootprint' => $propertyCarbonFootprint,
                'propertyCarbonFootprintId' => $propertyCarbonFootprintId,
                'step' => $step,
                'startForm' => $startForm?->createView(),
                'readOnly' => $readOnly,
                'subjectType' => MaterialityAssessmentSubjectType::Property->value,
            ],
        );
    }
}
