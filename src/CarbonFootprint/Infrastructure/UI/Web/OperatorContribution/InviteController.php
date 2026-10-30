<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OperatorContribution;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OperatorContribution\Register\RegisterOperatorContributionRequest;
use App\CarbonFootprint\Application\OperatorContribution\Register\RegisterOperatorContributionService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Infrastructure\Symfony\Form\InviteOperatorType;
use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\GetCompanyCarbonFootprintFromIdTrait;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/company-carbon-footprints/{companyCarbonFootprintId}/operators/{propertyId}/invite', name: 'company_carbon_footprints_operators_invite', methods: ['GET', 'POST'])]
class InviteController extends AbstractController
{
    use GetCompanyCarbonFootprintFromIdTrait;

    public function __invoke(
        string $companyCarbonFootprintId,
        string $propertyId,
        CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        CompanyProperties $companyProperties,
        RegisterOperatorContributionService $registerContribution,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $companyCarbonFootprint = $this->getCompanyCarbonFootprintFromId($companyCarbonFootprintId, $companyCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $companyCarbonFootprint);

        $stepParameters = ['companyCarbonFootprintId' => $companyCarbonFootprintId];

        if ($companyCarbonFootprint->isCompleted()) {
            $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

            return $this->redirectToRoute('company_carbon_footprints_scope_3', $stepParameters);
        }

        $property = $this->findNonConsolidatedProperty($companyProperties->nonConsolidated($companyCarbonFootprint->companyId()), $propertyId);
        $contact = $property->operatorContact();

        $form = $this->createForm(InviteOperatorType::class, [
            'contactEmail' => $contact->email(),
            'contactName' => $contact->name(),
            'contactLocale' => $contact->locale() ?? LocaleGetter::locale(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{contactEmail: string, contactName: ?string, contactLocale: Locale} $data */
            $data = $form->getData();

            try {
                $registerContribution(
                    new RegisterOperatorContributionRequest(
                        companyCarbonFootprintId: $companyCarbonFootprint->id(),
                        propertyId: $property->id(),
                        contactEmail: $data['contactEmail'],
                        contactName: $data['contactName'],
                        contactLocale: $data['contactLocale'],
                    ),
                    $loggedUser(),
                );

                $this->addFlash('success', new TranslatableMessage('Invitación enviada a {email}', ['{email}' => $data['contactEmail']]));

                return $this->redirectToRoute('company_carbon_footprints_scope_3', $stepParameters);
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (OperatorContributionClosedException) {
                $this->addFlash('warning', new TranslatableMessage('La huella está completada. Reábrela para modificarla.'));

                return $this->redirectToRoute('company_carbon_footprints_scope_3', $stepParameters);
            } catch (OperatorContributionAlreadyExistsException) {
                $this->addFlash('danger', new TranslatableMessage('Esta propiedad ya tiene una invitación activa'));

                return $this->redirectToRoute('company_carbon_footprints_scope_3', $stepParameters);
            }
        }

        return $this->render('@carbon_footprint/company_carbon_footprints/operator_contributions/invite.html.twig', [
            'form' => $form->createView(),
            'property' => $property,
            'companyCarbonFootprintId' => $companyCarbonFootprintId,
        ]);
    }

    /** @param list<Property> $properties */
    private function findNonConsolidatedProperty(array $properties, string $propertyId): Property
    {
        foreach ($properties as $property) {
            if ($property->id()->equals(new PropertyId($propertyId))) {
                return $property;
            }
        }

        throw $this->createNotFoundException('La propiedad no es una propiedad no consolidada de la empresa');
    }
}
