<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Form\RegisterUserType;
use App\Security\Infrastructure\UI\Web\User\RegisterUserTrait;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users/register', name: 'users_register')]
#[IsGranted("ROLE_OWNER")]
class RegisterController extends AbstractController
{
    use RegisterUserTrait, RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        Request $request
    ): Response
    {   
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException(
                new TranslatableMessage("Debes pertenecer a una cuenta para poder acceder")
            );
        }

        $user = new User(
            id: UserId::generate(),
            accountId: $account->id()
        );

        $performBy = $loggedUser();

        $this->denyAccessUnlessGranted(Action::Register->value, $user);

        try {
            $form = $this->createForm(RegisterUserType::class);

            $needsRedirect = $this->register($request, $form, function ($form) use ($loggedAccount, $performBy) {
                return $this->registerUser(
                    form: $form,
                    account: $loggedAccount(),
                    performBy: $performBy
                );
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('users');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/users/register.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
