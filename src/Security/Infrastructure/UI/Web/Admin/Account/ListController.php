<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\ListAccounts\ListAccountsRequest;
use App\Security\Application\ListAccounts\ListAccountsService;
use App\Security\Infrastructure\Symfony\Form\ListAccountsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/accounts', name: 'admin_accounts')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListAccountsService $listUsers, Request $request): Response
    {   
        list($filter, $form) = $this->filter($request, function () {
            return  new ListAccountsRequest();
        }, ListAccountsType::class);

        $response = $listUsers($filter);

        return $this->render('@security/accounts/list.html.twig', [
            'accounts' => $response->accounts,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
