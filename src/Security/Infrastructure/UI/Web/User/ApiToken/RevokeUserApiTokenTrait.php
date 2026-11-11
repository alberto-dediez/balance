<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User\ApiToken;

use App\Security\Application\Action;
use App\Security\Application\ApiToken\Revoke\RevokeApiTokenRequest;
use App\Security\Application\ApiToken\Revoke\RevokeApiTokenService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\GetUserFromIdTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;

trait RevokeUserApiTokenTrait
{
    use GetUserFromIdTrait;

    protected function revokeUserApiToken(
        string $userId,
        string $apiTokenId,
        UserRepository $userRepository,
        ApiTokenRepository $apiTokenRepository,
        RevokeApiTokenService $revokeApiToken,
        LoggedUser $loggedUser,
        string $listRoute,
    ): Response {
        $user = $this->getUserFromId($userId, $userRepository);

        $this->denyAccessUnlessGranted(Action::Edit->value, $user);

        $apiToken = $apiTokenRepository->find(new ApiTokenId($apiTokenId));

        if (null === $apiToken || !$apiToken->belongsTo($user->id())) {
            throw $this->createNotFoundException('ApiToken not found');
        }

        try {
            $revokeApiToken(new RevokeApiTokenRequest($apiToken->id()), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('warning', new TranslatableMessage('Token revocado'));

        return $this->redirectToRoute($listRoute, ['userId' => (string) $user->id()]);
    }
}
