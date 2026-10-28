<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults\GetPropertyCarbonFootprintResultsRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults\GetPropertyCarbonFootprintResultsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
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
        GetPropertyCarbonFootprintResultsService $getResults,
        OrganizationalResultsView $resultsView,
        ReportPermissionsPolicy $reportPermissionsPolicy,
        LoggedUser $loggedUser,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isProvidersAnswered()) {
            return $this->redirectToRoute(
                match (true) {
                    $propertyCarbonFootprint->status()->isScope3Answered() => 'property_carbon_footprints_providers',
                    $propertyCarbonFootprint->status()->isScope2Answered() => 'property_carbon_footprints_scope_3',
                    $propertyCarbonFootprint->status()->isScope1Answered() => 'property_carbon_footprints_scope_2',
                    default => 'property_carbon_footprints_scope_1',
                },
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        $user = $loggedUser();
        $results = $getResults(new GetPropertyCarbonFootprintResultsRequest($propertyCarbonFootprint->id()), $user);
        $report = $reportRepository->findBySubject(CarbonFootprintReportSubjectType::Property, (string) $propertyCarbonFootprint->id());

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
                'results' => $results,
                'report' => $report,
                'canEditReportTexts' => $reportPermissionsPolicy->permissionsFor($report?->accountId(), $user)->canEditTexts(),
            ],
        );
    }
}
