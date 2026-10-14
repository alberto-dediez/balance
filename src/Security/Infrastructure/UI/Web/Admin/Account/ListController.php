<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Application\Account\List\ListAccountsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Form\BulkAccountType;
use App\Security\Infrastructure\Symfony\Form\ListAccountsType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\Admin\Account\Bulk\BulkAccountRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/accounts', name: 'admin_accounts')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(ListAccountsService $listAccounts, LoggedUser $loggedUser, Request $request): Response
    {
        [$filter, $form] = $this->filter($request, fn(): ListAccountsRequest => new ListAccountsRequest(), ListAccountsType::class);

        try {
            $response = $listAccounts($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        $bulkForm = $this->createForm(BulkAccountType::class, new BulkAccountRequest(), [
            'elements' => $response->accounts,
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@security/admin/accounts/list.html.twig', [
            'bulkForm' => $bulkForm,
            'accounts' => $response->accounts,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
