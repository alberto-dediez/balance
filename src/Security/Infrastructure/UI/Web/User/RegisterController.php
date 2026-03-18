<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Symfony\Form\RegisterUserType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/users/register', name: 'users_register')]
#[IsGranted('ROLE_OWNER')]
class RegisterController extends AbstractController
{
    use RegisterUserTrait;
    use RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (!$account instanceof Account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $user = new User(
            id: UserId::generate(),
            accountId: $account->id(),
        );

        $performBy = $loggedUser();

        $this->denyAccessUnlessGranted(Action::Register->value, $user);

        try {
            $form = $this->createForm(RegisterUserType::class);

            $needsRedirect = $this->register($request, $form, fn(FormInterface $form): bool => $this->registerUser(
                form: $form,
                account: $loggedAccount(),
                performBy: $performBy,
            ));

            if ($needsRedirect) {
                return $this->redirectToRoute('users');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/users/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
