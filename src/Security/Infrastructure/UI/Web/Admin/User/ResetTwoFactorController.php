<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\DisableTwoFactor\DisableTwoFactorRequest;
use App\Security\Application\User\DisableTwoFactor\DisableTwoFactorService;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\GetUserFromIdTrait;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/users/{userId}/two-factor/reset', name: 'admin_users_two_factor_reset', methods: ['GET'])]
#[CsrfProtected('two-factor-reset')]
#[IsGranted('ROLE_USER')]
class ResetTwoFactorController extends AbstractController
{
    use GetUserFromIdTrait;

    public function __invoke(
        string $userId,
        LoggedUser $loggedUser,
        UserRepository $userRepository,
        DisableTwoFactorService $disableTwoFactor,
    ): Response {
        $user = $this->getUserFromId($userId, $userRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $user);

        try {
            $disableTwoFactor(new DisableTwoFactorRequest($user->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('warning', new TranslatableMessage('Segundo factor restablecido'));

        return $this->redirectToRoute('admin_users_edit', ['userId' => (string) $user->id()]);
    }
}
