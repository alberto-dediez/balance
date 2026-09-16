<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Step;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults\GetCompanyCarbonFootprintResultsRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults\GetCompanyCarbonFootprintResultsService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\OrganizationalResultsView;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/step/results', name: 'company_carbon_footprints_results')]
class ResultsController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        GetCompanyCarbonFootprintResultsService $getResults,
        OrganizationalResultsView $resultsView,
        LoggedUser $loggedUser,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $companyCarbonFootprint);

        if (!$companyCarbonFootprint->status()->isScope2Answered()) {
            return $this->redirectToRoute(
                $companyCarbonFootprint->status()->isScope1Answered() ? 'company_carbon_footprints_scope_2' : 'company_carbon_footprints_scope_1',
                ['companyCarbonFootprintId' => $companyCarbonFootprintId],
            );
        }

        $results = $getResults(new GetCompanyCarbonFootprintResultsRequest($companyCarbonFootprint->id()), $loggedUser());

        return $this->render(
            '@carbon_footprint/company_carbon_footprints/step/results.html.twig',
            $resultsView->build(
                $results->emissionsBySource,
                $results->emissionsByScope,
                $results->emissionsByScopeAndSource,
                $results->exclusions,
                $results->emissionsByPropertyId,
                $results->properties,
            ) + [
                'companyCarbonFootprint' => $companyCarbonFootprint,
                'companyCarbonFootprintId' => $companyCarbonFootprintId,
                'results' => $results,
            ],
        );
    }
}
