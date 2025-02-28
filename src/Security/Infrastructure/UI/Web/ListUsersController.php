<?php

namespace App\Security\Infrastructure\UI\Web;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Application\ListUsers\ListUsersRequest;
use App\Security\Application\ListUsers\ListUsersService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\UserFilterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/users', name: 'admin_users')]
#[IsGranted("ROLE_ADMIN")]
class ListUsersController extends AbstractController
{
    public function __construct(private ListUsersService $listUsers)
    {

    }

    public function __invoke(Request $request): Response
    {   
        $listUsers = $this->listUsers;

        $filter = new ListUsersRequest();

        $form = $this->createForm(UserFilterType::class, $filter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter = $form->getData();
        }

        $response = $listUsers($filter);

        return $this->render('@security/users/list.html.twig', [
            'users' => $response->users,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
