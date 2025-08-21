<?php

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Form\AdminBulkUserType;
use App\Security\Infrastructure\Symfony\Form\AdminListUsersType;
use App\Security\Infrastructure\UI\Web\User\Bulk\BulkUserRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/users', name: 'admin_users')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait, BulkTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        ListUsersService $listUsers,
        Request $request
    ): Response
    {   
        list($filter, $form) = $this->filter($request, function () {
            return  new ListUsersRequest();
        }, AdminListUsersType::class);

        try {
            $performBy = $loggedUser();

            $response = $listUsers($filter, $performBy);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(AdminBulkUserType::class, new BulkUserRequest, [
            'elements' => $response->users
        ]);

        $this->bulkActions($request, $bulkForm, $loggedUser());

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@security/admin/users/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'users' => $response->users,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
