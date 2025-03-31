<?php

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\AdminListUsersType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/users', name: 'admin_users')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListUsersService $listUsers,
        Request $request
    ): Response
    {   
        $user = new User(UserId::generate());

        $this->denyAccessUnlessGranted(Action::List->value, $user);

        list($filter, $form) = $this->filter($request, function () {
            return  new ListUsersRequest();
        }, AdminListUsersType::class);

        $response = $listUsers($filter);

        return $this->render('@security/admin/users/list.html.twig', [
            'users' => $response->users,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
