<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Symfony\Form\AdminRegisterUserType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\RegisterUserTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users/register', name: 'admin_users_register')]
#[IsGranted('ROLE_ADMIN')]
class RegisterController extends AbstractController
{
    use RegisterUserTrait;
    use RegisterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $user = new User(UserId::generate());

        $this->denyAccessUnlessGranted(Action::Register->value, $user);

        $form = $this->createForm(AdminRegisterUserType::class);

        $performBy = $loggedUser();

        try {

            $needsRedirect = $this->register($request, $form, fn(FormInterface $form): bool => $this->registerUser(
                form: $form,
                account: $form->get('account')->getData(),
                performBy: $performBy,
            ));

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_users');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/admin/users/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
