<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User\ApiToken;

use App\Security\Application\ApiToken\List\ListApiTokensService;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users/{userId}/api-tokens', name: 'users_api_tokens')]
#[IsGranted('ROLE_OWNER')]
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
        return $this->listUserApiTokens($userId, $userRepository, $listApiTokens, $loggedUser, $request, '@security/users/api-tokens.html.twig', 'users_api_tokens_revoke');
    }
}
