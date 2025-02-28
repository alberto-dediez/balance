<?php

namespace App\Security\Infrastructure\UI\Web;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\ListAccounts\ListAccountsRequest;
use App\Security\Application\ListAccounts\ListAccountsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\Symfony\Form\ListAccountsRequestType;

#[Route('/admin/accounts', name: 'admin_accounts')]
#[IsGranted("ROLE_ADMIN")]
class ListAccountsController extends AbstractController
{
    public function __construct(private ListAccountsService $listUsers)
    {

    }

    public function __invoke(Request $request): Response
    {   
        $listUsers = $this->listUsers;

        list($filter, $form) = $this->initiateFilter();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            if ($form->getClickedButton() && 'reset' == $form->getClickedButton()->getName()) {
                list($filter, $form) = $this->initiateFilter();
            }
        }

        $response = $listUsers($filter);

        return $this->render('@security/accounts/list.html.twig', [
            'accounts' => $response->accounts,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }

    private function initiateFilter()
    {
        $filter = new ListAccountsRequest();

        $form = $this->createForm(ListAccountsRequestType::class, $filter);

        return [$filter, $form];
    }
}
