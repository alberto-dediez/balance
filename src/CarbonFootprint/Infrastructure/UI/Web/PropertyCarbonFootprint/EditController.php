<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property-carbon-footprints/{propertyCarbonFootprintId}/edit', name: 'property_carbon_footprints_edit')]
class EditController extends AbstractController
{
    use GetPropertyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
    ): Response {
        $propertyCarbonFootprint = $this->getPropertyCarbonFootprintFromId($propertyCarbonFootprintId, $propertyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        return $this->redirectToRoute(
            match ($propertyCarbonFootprint->status()->activeStepKey()) {
                'scope-1' => 'property_carbon_footprints_scope_1',
                'scope-2' => 'property_carbon_footprints_scope_2',
                'scope-3' => 'property_carbon_footprints_scope_3',
                'providers' => 'property_carbon_footprints_providers',
                'employees' => 'property_carbon_footprints_employees',
                'mitigation-plan' => 'property_carbon_footprints_mitigation_plan',
                default => 'property_carbon_footprints_results',
            },
            ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
        );
    }
}
