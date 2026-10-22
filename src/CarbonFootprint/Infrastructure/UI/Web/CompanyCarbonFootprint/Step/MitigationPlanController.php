<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Step;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\MitigationAction\GetPlan\GetMitigationPlanRequest;
use App\CarbonFootprint\Application\MitigationAction\GetPlan\GetMitigationPlanService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\MitigationPlanView;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/step/mitigation-plan', name: 'company_carbon_footprints_mitigation_plan', methods: ['GET', 'POST'])]
class MitigationPlanController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        CarbonFootprintReportRepository $reportRepository,
        CompleteCompanyCarbonFootprintService $completeCompanyCarbonFootprint,
        GetMitigationPlanService $getMitigationPlan,
        MitigationPlanView $planView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$companyCarbonFootprint->status()->isScope3Answered()) {
            return $this->redirectToRoute(
                match (true) {
                    $companyCarbonFootprint->status()->isScope2Answered() => 'company_carbon_footprints_scope_3',
                    $companyCarbonFootprint->status()->isScope1Answered() => 'company_carbon_footprints_scope_2',
                    default => 'company_carbon_footprints_scope_1',
                },
                ['companyCarbonFootprintId' => $companyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $companyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('mitigation_plan_' . $companyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $completeCompanyCarbonFootprint(new CompleteCompanyCarbonFootprintRequest($companyCarbonFootprint->id()), $loggedUser());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (OrganizationalCarbonFootprintNotReadyToCompleteException) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido completar la huella de carbono.'));

                return $this->redirectToRoute('company_carbon_footprints_mitigation_plan', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
            }

            return $this->redirectToRoute('company_carbon_footprints_results', ['companyCarbonFootprintId' => $companyCarbonFootprintId]);
        }

        try {
            $plan = $getMitigationPlan(new GetMitigationPlanRequest(CarbonFootprintReportSubjectType::Company, $companyCarbonFootprintId), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render(
            '@carbon_footprint/company_carbon_footprints/step/mitigation-plan.html.twig',
            $planView->build($plan->actions, $plan->emissionSources, $plan->projection) + [
                'companyCarbonFootprint' => $companyCarbonFootprint,
                'companyCarbonFootprintId' => $companyCarbonFootprintId,
                'subjectType' => CarbonFootprintReportSubjectType::Company->value,
                'readOnly' => $companyCarbonFootprint->isCompleted(),
                'hasPreviousFootprint' => $plan->hasPreviousFootprint,
                'report' => $reportRepository->findBySubject(CarbonFootprintReportSubjectType::Company, (string) $companyCarbonFootprint->id()),
            ],
        );
    }
}
