<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Application\Account\List\ListAccountsService;
use App\Security\Infrastructure\Symfony\Form\ListAccountsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\UI\Web\Admin\Account\Bulk\RemoveAccount;

#[Route('/admin/accounts', name: 'admin_accounts')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait, BulkTrait;

    public function __invoke(ListAccountsService $listAccounts, LoggedUser $loggedUser, Request $request): Response
    {   
        [$filter, $form] = $this->filter($request, fn(): ListAccountsRequest => new ListAccountsRequest(), ListAccountsType::class);

        try {
            $response = $listAccounts($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest, [
            'elements' => $response->accounts,
            'actions' => [
                new RemoveAccount()
            ]
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@security/admin/accounts/list.html.twig', [
            'bulkForm' => $bulkForm,
            'accounts' => $response->accounts,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
