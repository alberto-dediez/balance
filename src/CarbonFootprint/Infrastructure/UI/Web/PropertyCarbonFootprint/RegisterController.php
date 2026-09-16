<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\EmissionFactorGroupNotConfiguredException;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAlreadyRegisteredException;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Register\RegisterPropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Register\RegisterPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterPropertyCarbonFootprintType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/property-carbon-footprints/register', name: 'property_carbon_footprints_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        EmissionFactorGroupRepository $emissionFactorGroupRepository,
        RegisterPropertyCarbonFootprintService $registerPropertyCarbonFootprint,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            accountId: $account->id(),
        ));

        $emissionFactorGroupId = $account->emissionFactorGroupId()
            ?? $emissionFactorGroupRepository->findDefault()?->id();

        if (null === $emissionFactorGroupId) {
            throw new EmissionFactorGroupNotConfiguredException($account->id());
        }

        $preselectedPropertyId = $request->query->getString('propertyId');

        $form = $this->createForm(
            RegisterPropertyCarbonFootprintType::class,
            new RegisterPropertyCarbonFootprintRequest(
                propertyId: '' !== $preselectedPropertyId ? new PropertyId($preselectedPropertyId) : null,
            ),
        );

        $registeredId = null;

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerPropertyCarbonFootprint, $account, $emissionFactorGroupId, $loggedUser, $translator, &$registeredId): bool {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $account->id();
                $registerRequest->emissionFactorGroupId = $emissionFactorGroupId;

                try {
                    $response = $registerPropertyCarbonFootprint($registerRequest, $loggedUser());
                } catch (PropertyCarbonFootprintAlreadyRegisteredException) {
                    $form->get('year')->addError(new FormError($translator->trans('Ya existe una huella de carbono para esta propiedad y año')));

                    return false;
                } catch (\InvalidArgumentException) {
                    $form->get('propertyId')->addError(new FormError($translator->trans('La propiedad no existe o no pertenece a tu cuenta')));

                    return false;
                }

                $registeredId = $response->propertyCarbonFootprintId;

                $this->addFlash('success', new TranslatableMessage('Cálculo de Huella de Carbono creado correctamente'));

                return true;
            });

            if ($needsRedirect && null !== $registeredId) {
                return $this->redirectToRoute('property_carbon_footprints_scope_1', ['propertyCarbonFootprintId' => (string) $registeredId]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/property_carbon_footprints/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
