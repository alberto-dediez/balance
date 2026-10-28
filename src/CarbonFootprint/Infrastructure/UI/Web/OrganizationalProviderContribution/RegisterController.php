<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Register\RegisterOrganizationalProviderContributionRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Register\RegisterOrganizationalProviderContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterOrganizationalProviderContributionType;
use App\EventOperations\Application\Provider\List\ListProvidersRequest;
use App\EventOperations\Application\Provider\List\ListProvidersService;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/property-carbon-footprints/{carbonFootprintId}/providers/register', name: 'property_carbon_footprints_providers_register', defaults: ['subjectType' => 'property'])]
#[Route('/company-carbon-footprints/{carbonFootprintId}/providers/register', name: 'company_carbon_footprints_providers_register', defaults: ['subjectType' => 'company'])]
class RegisterController extends AbstractController
{
    public function __invoke(
        string $subjectType,
        string $carbonFootprintId,
        OrganizationalCarbonFootprintLocator $locator,
        ListProvidersService $listProviders,
        CompanyProperties $companyProperties,
        RegisterOrganizationalProviderContributionService $registerContribution,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $type = CarbonFootprintReportSubjectType::from($subjectType);

        try {
            $located = $locator->locate($type, $carbonFootprintId);
            $located->denyUnlessCanDoThat(SecurityAction::Edit, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('El cálculo de huella de carbono no existe');
        }

        $stepRoute = OrganizationalProviderContributionRoutes::step($type);
        $stepParameters = OrganizationalProviderContributionRoutes::stepParameters($type, $carbonFootprintId);
        $carbonFootprint = $located->carbonFootprint;

        if ($carbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute($stepRoute, $stepParameters);
        }

        try {
            $providers = $listProviders(new ListProvidersRequest(accountId: $carbonFootprint->accountId(), limit: 0), $loggedUser())->providers;
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $form = $this->createForm(RegisterOrganizationalProviderContributionType::class, null, [
            'providers' => array_values($providers),
            'properties' => $carbonFootprint instanceof CompanyCarbonFootprint ? $companyProperties->consolidated($carbonFootprint->companyId()) : null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{provider: string, mode: ProviderContributionMode, sources: list<Source>, property?: ?string} $data */
            $data = $form->getData();

            $property = $data['property'] ?? null;
            $provider = $this->findProvider($providers, $data['provider']);

            if ($data['mode']->isRequested() && null !== $provider && null === $provider->userId()) {
                $this->addFlash('danger', new TranslatableMessage('El proveedor no tiene usuario vinculado'));

                return $this->redirectToRoute(OrganizationalProviderContributionRoutes::action($type, 'register'), ['carbonFootprintId' => $carbonFootprintId]);
            }

            try {
                $response = $registerContribution(
                    new RegisterOrganizationalProviderContributionRequest(
                        subjectType: $type,
                        carbonFootprintId: $carbonFootprintId,
                        providerId: new ProviderId($data['provider']),
                        propertyId: null === $property || '' === $property ? null : new PropertyId($property),
                        mode: $data['mode'],
                        sources: $data['sources'],
                    ),
                    $loggedUser(),
                );

                $this->addFlash('success', new TranslatableMessage('Aportación de proveedor registrada: {name}', ['{name}' => $provider?->name() ?? '']));

                if ($response->mode->isManual()) {
                    return $this->redirectToRoute(
                        OrganizationalProviderContributionRoutes::action($type, 'edit'),
                        ['carbonFootprintId' => $carbonFootprintId, 'contributionId' => (string) $response->contributionId],
                    );
                }

                return $this->redirectToRoute($stepRoute, $stepParameters);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (OrganizationalProviderContributionClosedException) {
                $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute($stepRoute, $stepParameters);
            } catch (OrganizationalProviderContributionAlreadyExistsException) {
                $this->addFlash('danger', new TranslatableMessage('Ya existe una aportación de este proveedor para esta huella'));
            } catch (\InvalidArgumentException) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido registrar la aportación del proveedor'));
            }

            return $this->redirectToRoute(OrganizationalProviderContributionRoutes::action($type, 'register'), ['carbonFootprintId' => $carbonFootprintId]);
        }

        return $this->render('@carbon_footprint/organizational_carbon_footprints/provider_contributions/register.html.twig', [
            'form' => $form->createView(),
            'carbonFootprint' => $carbonFootprint,
            'subjectType' => $subjectType,
            'stepPath' => $this->generateUrl($stepRoute, $stepParameters),
        ]);
    }

    /** @param array<Provider> $providers */
    private function findProvider(array $providers, string $providerId): ?Provider
    {
        foreach ($providers as $provider) {
            if ((string) $provider->id() === $providerId) {
                return $provider;
            }
        }

        return null;
    }
}
