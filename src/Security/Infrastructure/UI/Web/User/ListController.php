<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersService;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Form\BulkUserType;
use App\Security\Infrastructure\Symfony\Form\ListUsersType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\Bulk\BulkUserRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/users', name: 'users')]
#[IsGranted('ROLE_OWNER')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        ListUsersService $listUsers,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (!$account instanceof Account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder al listado de usuarios'));
        }

        $performBy = $loggedUser();

        try {
            [$filter, $form] = $this->filter($request, fn(): ListUsersRequest => new ListUsersRequest(
                account: $account,
            ), ListUsersType::class);

            $response = $listUsers($filter, $performBy);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkUserType::class, new BulkUserRequest(), [
            'elements' => $response->users,
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@security/users/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'users' => $response->users,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
