<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\CarbonFootprint;

use App\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions\LoadPropertyCarbonFootprintConsumptionsRequest;
use App\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions\LoadPropertyCarbonFootprintConsumptionsService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
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
    '/property-carbon-footprints/{propertyCarbonFootprintId}/step/{scope}/bioscore',
    name: 'property_carbon_footprints_bioscore_load',
    requirements: ['scope' => 'scope_1|scope_2'],
    methods: ['GET'],
)]
class LoadPropertyCarbonFootprintConsumptionsController extends AbstractController
{
    use BioscoreLoadFlashesTrait;

    public function __invoke(
        string $propertyCarbonFootprintId,
        string $scope,
        PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        LoadPropertyCarbonFootprintConsumptionsService $loadConsumptions,
        TranslatorInterface $translator,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $propertyCarbonFootprint = $propertyCarbonFootprintRepository->find(new PropertyCarbonFootprintId($propertyCarbonFootprintId));

        if (null === $propertyCarbonFootprint) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $propertyCarbonFootprint);

        if (!$this->isCsrfTokenValid('bioscore_load_' . $propertyCarbonFootprint->id(), $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $scopeEnum = Scope::from($scope);
        $stepRoute = match ($scopeEnum) {
            Scope::Scope1 => 'property_carbon_footprints_scope_1',
            Scope::Scope2 => 'property_carbon_footprints_scope_2',
            Scope::Scope3 => throw $this->createNotFoundException('Bioscore no admite el alcance 3'),
        };
        $redirect = $this->redirectToRoute($stepRoute, ['propertyCarbonFootprintId' => $propertyCarbonFootprintId]);

        try {
            $response = $loadConsumptions(
                new LoadPropertyCarbonFootprintConsumptionsRequest($propertyCarbonFootprint->id(), $scopeEnum),
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

        $this->flashResult($translator, $response->resolved, $response->activities, 1, $response->skipped, $response->includedSources);

        return $redirect;
    }
}
