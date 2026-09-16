<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Step;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults\GetPropertyCarbonFootprintResultsRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults\GetPropertyCarbonFootprintResultsService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\OrganizationalResultsView;
use App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\GetPropertyCarbonFootprintFromIdTrait;
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
        GetPropertyCarbonFootprintResultsService $getResults,
        OrganizationalResultsView $resultsView,
        LoggedUser $loggedUser,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $propertyCarbonFootprint);

        if (!$propertyCarbonFootprint->status()->isScope2Answered()) {
            return $this->redirectToRoute(
                $propertyCarbonFootprint->status()->isScope1Answered() ? 'property_carbon_footprints_scope_2' : 'property_carbon_footprints_scope_1',
                ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
            );
        }

        $results = $getResults(new GetPropertyCarbonFootprintResultsRequest($propertyCarbonFootprint->id()), $loggedUser());

        return $this->render(
            '@carbon_footprint/property_carbon_footprints/step/results.html.twig',
            $resultsView->build(
                $results->emissionsBySource,
                $results->emissionsByScope,
                $results->emissionsByScopeAndSource,
                $results->exclusions,
            ) + [
                'propertyCarbonFootprint' => $propertyCarbonFootprint,
                'propertyCarbonFootprintId' => $propertyCarbonFootprintId,
                'results' => $results,
            ],
        );
    }
}
