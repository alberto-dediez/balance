<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\Show\GetOrganizationalProviderContributionDetailRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Show\GetOrganizationalProviderContributionDetailService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property-carbon-footprints/{carbonFootprintId}/providers/{contributionId}', name: 'property_carbon_footprints_providers_show', requirements: ['contributionId' => '(?!register$)[^/]+'], methods: ['GET'], defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/{carbonFootprintId}/providers/{contributionId}', name: 'company_carbon_footprints_providers_show', requirements: ['contributionId' => '(?!register$)[^/]+'], methods: ['GET'], defaults: ['subjectType' => 'company'])]
class ShowController extends AbstractController
{
    use GetOrganizationalProviderContributionFromIdTrait;

    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        string $contributionId,
        OrganizationalProviderContributionRepository $contributionRepository,
        GetOrganizationalProviderContributionDetailService $getDetail,
        ScopeStepView $stepView,
        LoggedUser $loggedUser,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $contribution = $this->getOrganizationalProviderContributionFromId($contributionId, $type, $carbonFootprintId, $contributionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $contribution);

        if ($contribution->isManual()) {
            return $this->redirectToRoute(
                OrganizationalProviderContributionRoutes::action($type, 'edit'),
                ['carbonFootprintId' => $carbonFootprintId, 'contributionId' => $contributionId],
            );
        }

        try {
            $detail = $getDetail(new GetOrganizationalProviderContributionDetailRequest($contribution->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $sectionsBySource = [];
        foreach ($detail->sectionsBySource as $source => $sections) {
            $sectionsBySource[$source] = $stepView->build($sections);
        }

        return $this->render('@carbon_footprint/organizational_carbon_footprints/provider_contributions/show.html.twig', [
            'contribution' => $contribution,
            'provider' => $detail->provider,
            'property' => $detail->property,
            'carbonFootprint' => $detail->carbonFootprint,
            'subjectType' => $subjectType,
            'sectionsBySource' => $sectionsBySource,
            'stepPath' => $this->generateUrl(
                OrganizationalProviderContributionRoutes::step($type),
                OrganizationalProviderContributionRoutes::stepParameters($type, $carbonFootprintId),
            ),
        ]);
    }
}
