<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/edit', name: 'company_carbon_footprints_edit')]
class EditController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        return $this->redirectToRoute(
            match ($companyCarbonFootprint->status()->activeStepKey()) {
                'scope-1' => 'company_carbon_footprints_scope_1',
                'scope-2' => 'company_carbon_footprints_scope_2',
                'scope-3' => 'company_carbon_footprints_scope_3',
                'providers' => 'company_carbon_footprints_providers',
                'employees' => 'company_carbon_footprints_employees',
                'mitigation-plan' => 'company_carbon_footprints_mitigation_plan',
                default => 'company_carbon_footprints_results',
            },
            ['companyCarbonFootprintId' => $companyCarbonFootprintId],
        );
    }
}
