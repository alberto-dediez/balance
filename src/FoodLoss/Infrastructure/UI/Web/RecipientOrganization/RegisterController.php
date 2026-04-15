<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\RecipientOrganization;

use App\FoodLoss\Application\RecipientOrganization\Register\RegisterRecipientOrganizationRequest;
use App\FoodLoss\Application\RecipientOrganization\Register\RegisterRecipientOrganizationService;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganization;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterRecipientOrganizationType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/recipient-organizations/register', name: 'recipient_organizations_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterRecipientOrganizationService $registerRecipientOrganization, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new RecipientOrganization(
            id: RecipientOrganizationId::generate(),
            accountId: $loggedAccount()?->id(),
        ));

        $form = $this->createForm(RegisterRecipientOrganizationType::class, new RegisterRecipientOrganizationRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerRecipientOrganization, $loggedUser, $loggedAccount): true {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $loggedAccount()?->id();

                $response = $registerRecipientOrganization($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Entidad receptora creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('recipient_organizations');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/recipient-organizations/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
