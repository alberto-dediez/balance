<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\RecipientOrganization;

use App\FoodLoss\Application\RecipientOrganization\List\ListRecipientOrganizationsRequest;
use App\FoodLoss\Application\RecipientOrganization\List\ListRecipientOrganizationsService;
use App\FoodLoss\Infrastructure\Symfony\Form\ListRecipientOrganizationsType;
use App\FoodLoss\Infrastructure\UI\Web\RecipientOrganization\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recipient-organizations', name: 'recipient_organizations')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListRecipientOrganizationsService $listRecipientOrganizations, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn() => new ListRecipientOrganizationsRequest(
                accountId: $loggedAccount()?->id(),
            ), ListRecipientOrganizationsType::class);

            $response = $listRecipientOrganizations($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->recipientOrganizations,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@food_loss/recipient-organizations/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'recipientOrganizations' => $response->recipientOrganizations,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
