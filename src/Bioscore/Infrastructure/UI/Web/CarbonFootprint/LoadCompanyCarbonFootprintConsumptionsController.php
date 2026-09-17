<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\CarbonFootprint;

use App\Bioscore\Application\BioscoreConsumption\LoadCompanyCarbonFootprintConsumptions\LoadCompanyCarbonFootprintConsumptionsRequest;
use App\Bioscore\Application\BioscoreConsumption\LoadCompanyCarbonFootprintConsumptions\LoadCompanyCarbonFootprintConsumptionsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(
    '/company-carbon-footprints/{companyCarbonFootprintId}/step/{scope}/bioscore',
    name: 'company_carbon_footprints_bioscore_load',
    requirements: ['scope' => 'scope_1|scope_2'],
    methods: ['GET'],
)]
class LoadCompanyCarbonFootprintConsumptionsController extends AbstractController
{
    use BioscoreLoadFlashesTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        string $scope,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        LoadCompanyCarbonFootprintConsumptionsService $loadConsumptions,
        TranslatorInterface $translator,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $companyCarbonFootprintRepository->find(new CompanyCarbonFootprintId($companyCarbonFootprintId));

        if (null === $companyCarbonFootprint) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$this->isCsrfTokenValid('bioscore_load_' . $companyCarbonFootprint->id(), $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $scopeEnum = Scope::from($scope);
        $stepRoute = match ($scopeEnum) {
            Scope::Scope1 => 'company_carbon_footprints_scope_1',
            Scope::Scope2 => 'company_carbon_footprints_scope_2',
        };
        $redirect = $this->redirectToRoute($stepRoute, ['companyCarbonFootprintId' => $companyCarbonFootprintId]);

        try {
            $response = $loadConsumptions(
                new LoadCompanyCarbonFootprintConsumptionsRequest($companyCarbonFootprint->id(), $scopeEnum),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (BioscoreUnavailableException) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido conectar con Bioscore'));

            return $redirect;
        } catch (\InvalidArgumentException|\DomainException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se han podido cargar los consumos: {error}', ['{error}' => $e->getMessage()]));

            return $redirect;
        }

        if (0 === $response->totalProperties) {
            $this->addFlash('warning', new TranslatableMessage('La empresa no tiene propiedades'));

            return $redirect;
        }

        $this->flashResult($translator, $response->isResolved(), $response->activities, $response->resolvedProperties, $response->skipped, $response->includedSources);

        return $redirect;
    }
}
