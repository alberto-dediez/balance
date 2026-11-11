<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User\ApiToken;

use App\Security\Application\ApiToken\Revoke\RevokeApiTokenService;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users/{userId}/api-tokens/{apiTokenId}/revoke', name: 'users_api_tokens_revoke', methods: ['GET'])]
#[IsGranted('ROLE_OWNER')]
#[CsrfProtected('revoke')]
class RevokeController extends AbstractController
{
    use RevokeUserApiTokenTrait;

    public function __invoke(
        string $userId,
        string $apiTokenId,
        UserRepository $userRepository,
        ApiTokenRepository $apiTokenRepository,
        RevokeApiTokenService $revokeApiToken,
        LoggedUser $loggedUser,
    ): Response {
        return $this->revokeUserApiToken($userId, $apiTokenId, $userRepository, $apiTokenRepository, $revokeApiToken, $loggedUser, 'users_api_tokens');
    }
}
