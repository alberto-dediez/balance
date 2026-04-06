<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\RecipientOrganization;

use App\FoodLoss\Application\RecipientOrganization\Remove\RemoveRecipientOrganizationRequest;
use App\FoodLoss\Application\RecipientOrganization\Remove\RemoveRecipientOrganizationService;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/recipient-organizations/remove/{recipientOrganizationId}', name: 'recipient_organizations_remove')]
class RemoveController extends AbstractController
{
    use GetRecipientOrganizationFromIdTrait;

    public function __invoke(
        string $recipientOrganizationId,
        RecipientOrganizationRepository $recipientOrganizationRepository,
        RemoveRecipientOrganizationService $removeRecipientOrganization,
        LoggedUser $loggedUser,
    ): Response {
        $recipientOrganization = $this->getRecipientOrganizationFromId($recipientOrganizationId, $recipientOrganizationRepository);

        try {
            $removeRecipientOrganization(new RemoveRecipientOrganizationRequest($recipientOrganization->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado la entidad receptora {name}', ['{name}' => $recipientOrganization->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar la entidad receptora {name}', ['{name}' => $recipientOrganization->name()]));
        }

        return $this->redirectToRoute('recipient_organizations');
    }
}
