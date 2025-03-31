<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Infrastructure\Symfony\Form\ListUsersType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users', name: 'users')]
#[IsGranted("ROLE_OWNER")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListUsersService $listUsers,
        LoggedAccount $loggedAccount,
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder al listado de usuarios")
            );
        }

        $user = new User(UserId::generate());

        $this->denyAccessUnlessGranted(Action::List->value, $user);

        list($filter, $form) = $this->filter($request, function () use ($account) {
            return  new ListUsersRequest(
                account: $account
            );
        }, ListUsersType::class);

        $response = $listUsers($filter);

        return $this->render('@security/users/list.html.twig', [
            'users' => $response->users,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
