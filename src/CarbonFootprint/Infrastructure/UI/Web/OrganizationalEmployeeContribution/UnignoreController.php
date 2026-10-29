<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Ignore\UnignoreOrganizationalEmployeeContributionRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Ignore\UnignoreOrganizationalEmployeeContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{carbonFootprintId}/employees/{contributionId}/unignore', name: 'property_carbon_footprints_employees_unignore', methods: ['GET'], defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/{carbonFootprintId}/employees/{contributionId}/unignore', name: 'company_carbon_footprints_employees_unignore', methods: ['GET'], defaults: ['subjectType' => 'company'])]
#[CsrfProtected('unignore')]
class UnignoreController extends AbstractController
{
    use GetOrganizationalEmployeeContributionFromIdTrait;

    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        string $contributionId,
        OrganizationalEmployeeContributionRepository $contributionRepository,
        UnignoreOrganizationalEmployeeContributionService $service,
        LoggedUser $loggedUser,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $contribution = $this->getOrganizationalEmployeeContributionFromId($contributionId, $type, $carbonFootprintId, $contributionRepository);
        $employeeName = $contribution->employeeName() ?? '';

        try {
            $service(new UnignoreOrganizationalEmployeeContributionRequest($contribution->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha reactivado la aportación de {name}', ['{name}' => $employeeName]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (OrganizationalProviderContributionClosedException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        }

        return $this->redirectToRoute(
            OrganizationalEmployeeContributionRoutes::step($type),
            OrganizationalEmployeeContributionRoutes::stepParameters($type, $carbonFootprintId),
        );
    }
}
