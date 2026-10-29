<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show\GetOrganizationalEmployeeContributionDetailRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show\GetOrganizationalEmployeeContributionDetailService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property-carbon-footprints/{carbonFootprintId}/employees/{contributionId}', name: 'property_carbon_footprints_employees_show', methods: ['GET'], defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/{carbonFootprintId}/employees/{contributionId}', name: 'company_carbon_footprints_employees_show', methods: ['GET'], defaults: ['subjectType' => 'company'])]
class ShowController extends AbstractController
{
    use GetOrganizationalEmployeeContributionFromIdTrait;

    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        string $contributionId,
        OrganizationalEmployeeContributionRepository $contributionRepository,
        OrganizationalCarbonFootprintLocator $locator,
        GetOrganizationalEmployeeContributionDetailService $getDetail,
        LoggedUser $loggedUser,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $contribution = $this->getOrganizationalEmployeeContributionFromId($contributionId, $type, $carbonFootprintId, $contributionRepository);

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $contribution);

        try {
            $detail = $getDetail(new GetOrganizationalEmployeeContributionDetailRequest($contribution->id()), $loggedUser());
            $located = $locator->locate($type, $carbonFootprintId);
            $located->denyUnlessCanDoThat(SecurityAction::View, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Huella de carbono no encontrada');
        }

        return $this->render('@carbon_footprint/organizational_carbon_footprints/employee_contributions/show.html.twig', [
            'contribution' => $contribution,
            'employee' => $detail->employee,
            'workplace' => $detail->workplace,
            'mobilitiesById' => $detail->mobilitiesById,
            'carbonFootprint' => $located->carbonFootprint,
            'subjectType' => $subjectType,
            'stepPath' => $this->generateUrl(
                OrganizationalEmployeeContributionRoutes::step($type),
                OrganizationalEmployeeContributionRoutes::stepParameters($type, $carbonFootprintId),
            ),
        ]);
    }
}
