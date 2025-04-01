<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Application\Account\List\ListAccountsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Form\ListAccountsType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/accounts', name: 'admin_accounts')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListAccountsService $listAccounts, LoggedUser $loggedUser, Request $request): Response
    {   
        list($filter, $form) = $this->filter($request, function () {
            return  new ListAccountsRequest();
        }, ListAccountsType::class);

        try {
            $response = $listAccounts($filter, $loggedUser());
        } catch (UnauthorizedActionException $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        return $this->render('@security/admin/accounts/list.html.twig', [
            'accounts' => $response->accounts,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
