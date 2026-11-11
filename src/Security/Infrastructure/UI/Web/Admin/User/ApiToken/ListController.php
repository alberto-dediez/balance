<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\User\ApiToken;

use App\Security\Application\ApiToken\List\ListApiTokensService;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\ApiToken\ListUserApiTokensTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users/{userId}/api-tokens', name: 'admin_users_api_tokens')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use ListUserApiTokensTrait;

    public function __invoke(
        string $userId,
        UserRepository $userRepository,
        ListApiTokensService $listApiTokens,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        return $this->listUserApiTokens($userId, $userRepository, $listApiTokens, $loggedUser, $request, '@security/admin/users/api-tokens.html.twig', 'admin_users_api_tokens_revoke');
    }
}
