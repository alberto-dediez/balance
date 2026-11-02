<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\MaterialityAssessment\Step;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerMateriality\AnswerCompanyCarbonFootprintMaterialityRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerMateriality\AnswerCompanyCarbonFootprintMaterialityService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
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

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/step/materiality', name: 'company_carbon_footprints_materiality', methods: ['GET', 'POST'])]
class CompanyMaterialityStepController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        AnswerCompanyCarbonFootprintMaterialityService $answerMateriality,
        GetMaterialityStepService $getMaterialityStep,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$companyCarbonFootprint->status()->isScope2Answered()) {
            return $this->redirectToRoute(
                $companyCarbonFootprint->status()->isScope1Answered() ? 'company_carbon_footprints_scope_2' : 'company_carbon_footprints_scope_1',
                ['companyCarbonFootprintId' => $companyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $companyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('company_carbon_footprints_materiality', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('materiality_skip_' . $companyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $answerMateriality(new AnswerCompanyCarbonFootprintMaterialityRequest($companyCarbonFootprint->id()), $loggedUser());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            return $this->redirectToRoute('company_carbon_footprints_scope_3', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        try {
            $step = $getMaterialityStep(new GetMaterialityStepRequest(MaterialityAssessmentSubjectType::Company, $companyCarbonFootprintId), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $readOnly = $companyCarbonFootprint->isCompleted();

        $startForm = null === $step->assessment && !$readOnly
            ? $this->createForm(StartMaterialityAssessmentType::class, null, [
                'action' => $this->generateUrl('materiality_assessments_start', ['subjectType' => MaterialityAssessmentSubjectType::Company->value, 'carbonFootprintId' => $companyCarbonFootprintId]),
                'previous_year' => $step->previousYear,
                'default_sector_id' => $step->previousSectorId,
                'sectors_available' => [] !== $step->sectors,
            ])
            : null;

        return $this->render(
            '@materiality/assessment/step/company.html.twig',
            [
                'companyCarbonFootprint' => $companyCarbonFootprint,
                'companyCarbonFootprintId' => $companyCarbonFootprintId,
                'step' => $step,
                'startForm' => $startForm?->createView(),
                'readOnly' => $readOnly,
                'subjectType' => MaterialityAssessmentSubjectType::Company->value,
            ],
        );
    }
}
