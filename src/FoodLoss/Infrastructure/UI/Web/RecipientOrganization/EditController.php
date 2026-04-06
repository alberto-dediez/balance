<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\RecipientOrganization;

use App\FoodLoss\Application\RecipientOrganization\Update\UpdateRecipientOrganizationRequest;
use App\FoodLoss\Application\RecipientOrganization\Update\UpdateRecipientOrganizationService;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\UpdateRecipientOrganizationType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/recipient-organizations/edit/{recipientOrganizationId}', name: 'recipient_organizations_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetRecipientOrganizationFromIdTrait;

    public function __invoke(
        string $recipientOrganizationId,
        RecipientOrganizationRepository $recipientOrganizationRepository,
        UpdateRecipientOrganizationService $updateRecipientOrganization,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $recipientOrganization = $this->getRecipientOrganizationFromId($recipientOrganizationId, $recipientOrganizationRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $recipientOrganization);

        $recipientOrganizationRequest = new UpdateRecipientOrganizationRequest(
            recipientOrganizationId: $recipientOrganization->id(),
            name: $recipientOrganization->name(),
            type: $recipientOrganization->type(),
            nif: $recipientOrganization->nif(),
            socialReason: $recipientOrganization->socialReason(),
            address: $recipientOrganization->address(),
            contactName: $recipientOrganization->contact()->name(),
            contactEmail: $recipientOrganization->contact()->email(),
            contactPhone: $recipientOrganization->contact()->phone(),
            operatingHours: $recipientOrganization->operatingHours(),
            pickupTypes: $recipientOrganization->pickupTypes(),
        );

        try {
            $form = $this->createForm(UpdateRecipientOrganizationType::class, $recipientOrganizationRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateRecipientOrganization, $loggedUser): void {
                $updateRequest = $form->getData();

                $updateRecipientOrganization($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Entidad receptora actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('recipient_organizations');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/recipient-organizations/edit.html.twig', [
            'recipientOrganization' => $recipientOrganization,
            'form' => $form->createView(),
        ]);
    }
}
