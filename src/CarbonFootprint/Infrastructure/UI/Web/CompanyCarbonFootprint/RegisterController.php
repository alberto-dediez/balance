<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAlreadyRegisteredException;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Register\RegisterCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Register\RegisterCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EmissionFactorGroupNotConfiguredException;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterCompanyCarbonFootprintType;
use App\Property\Domain\Model\Company\CompanyId;
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

#[Route('/company-carbon-footprints/register', name: 'company_carbon_footprints_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        EmissionFactorGroupRepository $emissionFactorGroupRepository,
        RegisterCompanyCarbonFootprintService $registerCompanyCarbonFootprint,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new CompanyCarbonFootprint(
            id: CompanyCarbonFootprintId::generate(),
            accountId: $account->id(),
        ));

        $emissionFactorGroupId = $account->emissionFactorGroupId()
            ?? $emissionFactorGroupRepository->findDefault()?->id();

        if (null === $emissionFactorGroupId) {
            throw new EmissionFactorGroupNotConfiguredException($account->id());
        }

        $preselectedCompanyId = $request->query->getString('companyId');

        $form = $this->createForm(
            RegisterCompanyCarbonFootprintType::class,
            new RegisterCompanyCarbonFootprintRequest(
                companyId: '' !== $preselectedCompanyId ? new CompanyId($preselectedCompanyId) : null,
            ),
        );

        $registeredId = null;

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCompanyCarbonFootprint, $account, $emissionFactorGroupId, $loggedUser, $translator, &$registeredId): bool {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $account->id();
                $registerRequest->emissionFactorGroupId = $emissionFactorGroupId;

                try {
                    $response = $registerCompanyCarbonFootprint($registerRequest, $loggedUser());
                } catch (CompanyCarbonFootprintAlreadyRegisteredException) {
                    $form->get('year')->addError(new FormError($translator->trans('Ya existe una huella de carbono para esta empresa y año')));

                    return false;
                } catch (\InvalidArgumentException) {
                    $form->get('companyId')->addError(new FormError($translator->trans('La empresa no existe o no pertenece a tu cuenta')));

                    return false;
                }

                $registeredId = $response->companyCarbonFootprintId;

                $this->addFlash('success', new TranslatableMessage('Cálculo de Huella de Carbono creado correctamente'));

                return true;
            });

            if ($needsRedirect && null !== $registeredId) {
                return $this->redirectToRoute('company_carbon_footprints_scope_1', ['companyCarbonFootprintId' => (string) $registeredId]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/company_carbon_footprints/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
