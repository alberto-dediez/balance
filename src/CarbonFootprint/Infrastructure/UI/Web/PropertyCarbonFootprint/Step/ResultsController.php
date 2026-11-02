<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\DeclareIntensityDenominators\DeclareIntensityDenominatorsRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults\GetPropertyCarbonFootprintResultsRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults\GetPropertyCarbonFootprintResultsService;
use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Issue\IssuePropertyCarbonFootprintCertificateRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorsCalculator;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\IntensityDenominatorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\IssuePropertyCarbonFootprintCertificateType;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\OrganizationalResultsView;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/step/results', name: 'property_carbon_footprints_results')]
class ResultsController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        CarbonFootprintReportRepository $reportRepository,
        PropertyCarbonFootprintCertificateRepository $certificateRepository,
        GetPropertyCarbonFootprintResultsService $getResults,
        OrganizationalResultsView $resultsView,
        IntensityIndicatorsCalculator $intensityIndicatorsCalculator,
        ReportPermissionsPolicy $reportPermissionsPolicy,
        LoggedUser $loggedUser,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isEmployeesAnswered()) {
            return $this->redirectToRoute(
                match (true) {
                    $propertyCarbonFootprint->status()->isProvidersAnswered() => 'property_carbon_footprints_employees',
                    $propertyCarbonFootprint->status()->isScope3Answered() => 'property_carbon_footprints_providers',
                    $propertyCarbonFootprint->status()->isMaterialityAnswered() => 'property_carbon_footprints_scope_3',
                    $propertyCarbonFootprint->status()->isScope2Answered() => 'property_carbon_footprints_materiality',
                    $propertyCarbonFootprint->status()->isScope1Answered() => 'property_carbon_footprints_scope_2',
                    default => 'property_carbon_footprints_scope_1',
                },
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        $user = $loggedUser();
        $results = $getResults(new GetPropertyCarbonFootprintResultsRequest($propertyCarbonFootprint->id()), $user);
        $report = $reportRepository->findBySubject(CarbonFootprintReportSubjectType::Property, (string) $propertyCarbonFootprint->id());
        $certificateForm = $this->createForm(
            IssuePropertyCarbonFootprintCertificateType::class,
            new IssuePropertyCarbonFootprintCertificateRequest($propertyCarbonFootprint->id(), $propertyCarbonFootprint->intensityDenominators()->stays()),
            ['action' => $this->generateUrl('property_carbon_footprints_certificate_issue', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId])],
        );

        $denominatorsForm = $this->createForm(
            IntensityDenominatorsType::class,
            DeclareIntensityDenominatorsRequest::fromDenominators($propertyCarbonFootprint->id(), $propertyCarbonFootprint->intensityDenominators()),
            [
                'data_class' => DeclareIntensityDenominatorsRequest::class,
                'action' => $this->generateUrl('property_carbon_footprints_declare_denominators', ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]),
            ],
        );

        return $this->render(
            '@carbon_footprint/property_carbon_footprints/step/results.html.twig',
            $resultsView->build(
                $results->emissionsBySource,
                $results->emissionsByScope,
                $results->emissionsByScopeAndSource,
                $results->exclusions,
                scope3Exclusion: $results->scope3Exclusion,
            ) + [
                'propertyCarbonFootprint' => $propertyCarbonFootprint,
                'propertyCarbonFootprintId' => $propertyCarbonFootprintId,
                'precalculated' => $propertyCarbonFootprint->isPrecalculated(),
                'results' => $results,
                'report' => $report,
                'canEditReportTexts' => $reportPermissionsPolicy->permissionsFor($report?->accountId(), $user)->canEditTexts(),
                'reportGenerated' => null !== $report && $report->status()->isGenerated(),
                'certificate' => $certificateRepository->findCurrentByPropertyCarbonFootprintId($propertyCarbonFootprint->id()),
                'certificateForm' => $certificateForm->createView(),
                'denominatorsForm' => $denominatorsForm->createView(),
                'denominatorsPrefilled' => false,
                'intensityIndicators' => $intensityIndicatorsCalculator->calculate((float) $results->totalEmissions->co2eq(), $propertyCarbonFootprint->intensityDenominators()),
                'kpiPath' => $this->generateUrl('property_carbon_footprint_kpis', ['propertyId' => (string) $propertyCarbonFootprint->propertyId(), 'year' => $propertyCarbonFootprint->year()]),
            ],
        );
    }
}
