<?php

namespace App\Security\Infrastructure\UI\Web;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\ListUsers\ListUsersRequest;
use App\Security\Application\ListUsers\ListUsersService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\ListUsersType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users', name: 'users')]
#[IsGranted("ROLE_OWNER")]
class ListUsersController extends AbstractController
{
    public function __construct(private ListUsersService $listUsers, private Security $security)
    {

    }

    public function __invoke(Request $request): Response
    {   
        $accountId = $this->security->getUser()->accountId();

        if (null == $accountId) {
            throw $this->createAccessDeniedException(new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder al listado de usuarios"));
        }

        $account = new Account($accountId);

        $listUsers = $this->listUsers;

        list($filter, $form) = $this->initiateFilter($account);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();

            if ($form->getClickedButton() && 'reset' == $form->getClickedButton()->getName()) {
                list($filter, $form) = $this->initiateFilter($account);
            }
        }

        $response = $listUsers($filter);

        return $this->render('@security/users/list.html.twig', [
            'users' => $response->users,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }

    private function initiateFilter(Account $account)
    {
        $filter = new ListUsersRequest(
            account: $account
        );

        $form = $this->createForm(ListUsersType::class, $filter);

        return [$filter, $form];
    }
}
