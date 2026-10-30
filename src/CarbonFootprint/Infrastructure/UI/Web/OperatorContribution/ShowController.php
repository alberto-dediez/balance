<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OperatorContribution;

use App\CarbonFootprint\Application\OperatorContribution\Show\GetOperatorContributionDetailRequest;
use App\CarbonFootprint\Application\OperatorContribution\Show\GetOperatorContributionDetailService;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\ScopeStepView;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/operators/{contributionId}', name: 'company_carbon_footprints_operators_show', methods: ['GET'])]
class ShowController extends AbstractController
{
    use GetOperatorContributionFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        string $contributionId,
        OperatorContributionRepository $contributionRepository,
        GetOperatorContributionDetailService $getDetail,
        ScopeStepView $stepView,
        LoggedUser $loggedUser,
    ): Response {
        $contribution = $this->getOperatorContributionFromId($contributionId, $companyCarbonFootprintId, $contributionRepository);

        try {
            $detail = $getDetail(new GetOperatorContributionDetailRequest($contribution->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/company_carbon_footprints/operator_contributions/show.html.twig', [
            'contribution' => $detail->contribution,
            'property' => $detail->property,
            'summary' => $detail->summary,
            'scope1' => $stepView->build($detail->scope1Sections),
            'scope2' => $stepView->build($detail->scope2Sections),
            'companyCarbonFootprintId' => $companyCarbonFootprintId,
        ]);
    }
}
