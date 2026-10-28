<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\Remove\RemoveOrganizationalProviderContributionRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Remove\RemoveOrganizationalProviderContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{carbonFootprintId}/providers/{contributionId}/remove', name: 'property_carbon_footprints_providers_remove', methods: ['GET'], defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/{carbonFootprintId}/providers/{contributionId}/remove', name: 'company_carbon_footprints_providers_remove', methods: ['GET'], defaults: ['subjectType' => 'company'])]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetOrganizationalProviderContributionFromIdTrait;

    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        string $contributionId,
        OrganizationalProviderContributionRepository $contributionRepository,
        RemoveOrganizationalProviderContributionService $removeContribution,
        ProviderRepository $providerRepository,
        LoggedUser $loggedUser,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);
        $contribution = $this->getOrganizationalProviderContributionFromId($contributionId, $type, $carbonFootprintId, $contributionRepository);
        $providerName = $this->organizationalProviderContributionName($contribution, $providerRepository);

        try {
            $removeContribution(new RemoveOrganizationalProviderContributionRequest($contribution->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la aportación de {name}', ['{name}' => $providerName]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (OrganizationalProviderContributionClosedException) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));
        } catch (\LogicException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la aportación de {name}', ['{name}' => $providerName]));
        }

        return $this->redirectToRoute(
            OrganizationalProviderContributionRoutes::step($type),
            OrganizationalProviderContributionRoutes::stepParameters($type, $carbonFootprintId),
        );
    }
}
