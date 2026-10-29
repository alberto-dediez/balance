<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Resend\ResendOrganizationalEmployeeContributionNotificationRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Resend\ResendOrganizationalEmployeeContributionNotificationService;
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

#[Route('/property-carbon-footprints/{carbonFootprintId}/employees/{contributionId}/resend', name: 'property_carbon_footprints_employees_resend', methods: ['GET'], defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/{carbonFootprintId}/employees/{contributionId}/resend', name: 'company_carbon_footprints_employees_resend', methods: ['GET'], defaults: ['subjectType' => 'company'])]
#[CsrfProtected('resend')]
class ResendController extends AbstractController
{
    use GetOrganizationalEmployeeContributionFromIdTrait;

    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        string $contributionId,
        OrganizationalEmployeeContributionRepository $contributionRepository,
        ResendOrganizationalEmployeeContributionNotificationService $service,
        LoggedUser $loggedUser,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $contribution = $this->getOrganizationalEmployeeContributionFromId($contributionId, $type, $carbonFootprintId, $contributionRepository);
        $employeeName = $contribution->employeeName() ?? '';

        try {
            $service(new ResendOrganizationalEmployeeContributionNotificationRequest($contribution->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha reenviado la encuesta a {name}', ['{name}' => $employeeName]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (OrganizationalProviderContributionClosedException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        } catch (\LogicException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido reenviar la encuesta a {name}', ['{name}' => $employeeName]));
        }

        return $this->redirectToRoute(
            OrganizationalEmployeeContributionRoutes::step($type),
            OrganizationalEmployeeContributionRoutes::stepParameters($type, $carbonFootprintId),
        );
    }
}
