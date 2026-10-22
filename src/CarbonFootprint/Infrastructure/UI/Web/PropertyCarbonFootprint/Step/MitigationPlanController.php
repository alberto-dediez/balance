<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\MitigationAction\GetPlan\GetMitigationPlanRequest;
use App\CarbonFootprint\Application\MitigationAction\GetPlan\GetMitigationPlanService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\MitigationPlanView;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/step/mitigation-plan', name: 'property_carbon_footprints_mitigation_plan', methods: ['GET', 'POST'])]
class MitigationPlanController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        CarbonFootprintReportRepository $reportRepository,
        CompletePropertyCarbonFootprintService $completePropertyCarbonFootprint,
        GetMitigationPlanService $getMitigationPlan,
        MitigationPlanView $planView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isScope3Answered()) {
            return $this->redirectToRoute(
                match (true) {
                    $propertyCarbonFootprint->status()->isScope2Answered() => 'property_carbon_footprints_scope_3',
                    $propertyCarbonFootprint->status()->isScope1Answered() => 'property_carbon_footprints_scope_2',
                    default => 'property_carbon_footprints_scope_1',
                },
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        if ($request->isMethod('POST') && $propertyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('mitigation_plan_' . $propertyCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            try {
                $completePropertyCarbonFootprint(new CompletePropertyCarbonFootprintRequest($propertyCarbonFootprint->id()), $loggedUser());
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (OrganizationalCarbonFootprintNotReadyToCompleteException) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido completar la huella de carbono.'));

                return $this->redirectToRoute('property_carbon_footprints_mitigation_plan', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
            }

            return $this->redirectToRoute('property_carbon_footprints_results', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);
        }

        try {
            $plan = $getMitigationPlan(new GetMitigationPlanRequest(CarbonFootprintReportSubjectType::Property, $propertyCarbonFootprintId), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render(
            '@carbon_footprint/property_carbon_footprints/step/mitigation-plan.html.twig',
            $planView->build($plan->actions, $plan->emissionSources, $plan->projection) + [
                'propertyCarbonFootprint' => $propertyCarbonFootprint,
                'propertyCarbonFootprintId' => $propertyCarbonFootprintId,
                'subjectType' => CarbonFootprintReportSubjectType::Property->value,
                'readOnly' => $propertyCarbonFootprint->isCompleted(),
                'hasPreviousFootprint' => $plan->hasPreviousFootprint,
                'report' => $reportRepository->findBySubject(CarbonFootprintReportSubjectType::Property, (string) $propertyCarbonFootprint->id()),
            ],
        );
    }
}
