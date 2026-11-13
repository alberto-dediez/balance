<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\LoadPropertyFootprintsIntoCompanyCarbonFootprintResponse;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\LoadPropertyFootprintsIntoCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\PropertyEmissionsDifference;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints\SkippedPropertyFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
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
    '/company-carbon-footprints/{companyCarbonFootprintId}/step/{scope}/property-footprints',
    name: 'company_carbon_footprints_property_footprints_load',
    requirements: ['scope' => 'scope_1|scope_2'],
    methods: ['GET'],
)]
class LoadPropertyFootprintsController extends AbstractController
{
    public function __invoke(
        string $companyCarbonFootprintId,
        string $scope,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        LoadPropertyFootprintsIntoCompanyCarbonFootprintService $loadPropertyFootprints,
        TranslatorInterface $translator,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $companyCarbonFootprintRepository->find(new CompanyCarbonFootprintId($companyCarbonFootprintId));

        if (null === $companyCarbonFootprint) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        if (!$this->isCsrfTokenValid('property_footprints_load_' . $companyCarbonFootprint->id(), $request->query->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $scopeEnum = Scope::from($scope);
        $stepRoute = match ($scopeEnum) {
            Scope::Scope1 => 'company_carbon_footprints_scope_1',
            Scope::Scope2 => 'company_carbon_footprints_scope_2',
            Scope::Scope3 => throw $this->createNotFoundException('Solo se pueden cargar los alcances 1 y 2'),
        };
        $redirect = $this->redirectToRoute($stepRoute, ['companyCarbonFootprintId' => $companyCarbonFootprintId]);

        try {
            $response = $loadPropertyFootprints(
                new LoadPropertyFootprintsIntoCompanyCarbonFootprintRequest($companyCarbonFootprint->id(), $scopeEnum),
                $loggedUser(),
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException|\DomainException $e) {
            $this->addFlash('danger', new TranslatableMessage('No se han podido cargar las huellas de propiedad: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

            return $redirect;
        }

        $this->flashResult($translator, $response);

        return $redirect;
    }

    private function flashResult(TranslatorInterface $translator, LoadPropertyFootprintsIntoCompanyCarbonFootprintResponse $response): void
    {
        if (0 === $response->totalProperties) {
            $this->addFlash('warning', new TranslatableMessage('La empresa no tiene propiedades'));

            return;
        }

        if (!$response->isResolved()) {
            $this->addFlash('warning', new TranslatableMessage('Ninguna propiedad de la empresa tiene una huella completada con actividades en este alcance'));
        } else {
            $this->addFlash('success', new TranslatableMessage('Actividades copiadas desde huellas de propiedad: {activities} actividades en {properties} propiedad(es)', [
                '{activities}' => $response->activities,
                '{properties}' => $response->copiedProperties,
            ]));
        }

        if ([] !== $response->includedSources) {
            $this->addFlash('warning', new TranslatableMessage('Se han vuelto a incluir las fuentes: {sources}', [
                '{sources}' => implode(', ', array_map(
                    static fn(Source $source): string => $translator->trans($source->value, [], 'source'),
                    $response->includedSources,
                )),
            ]));
        }

        if ([] !== $response->skipped) {
            $this->addFlash('warning', new TranslatableMessage('Propiedades omitidas ({count}): {items}', [
                '{count}' => count($response->skipped),
                '{items}' => implode('; ', array_map(
                    static fn(SkippedPropertyFootprint $item): string => sprintf(
                        '%s: %s',
                        $item->propertyName,
                        $translator->trans($item->reason->value, [], 'propertyFootprintSkipReason'),
                    ),
                    $response->skipped,
                )),
            ]));
        }

        if ([] !== $response->differences) {
            $this->addFlash('warning', new TranslatableMessage('Las emisiones recalculadas con los factores de emisión vigentes difieren de las de la huella de propiedad: {items}', [
                '{items}' => implode('; ', array_map(
                    static fn(PropertyEmissionsDifference $item): string => sprintf(
                        '%s: %s → %s kg CO₂eq',
                        $item->propertyName,
                        number_format((float) $item->propertyFootprintCo2eq, 2, ',', '.'),
                        number_format((float) $item->recalculatedCo2eq, 2, ',', '.'),
                    ),
                    $response->differences,
                )),
            ]));
        }
    }
}
