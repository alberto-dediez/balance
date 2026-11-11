<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\RevokeAll;

use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\User\User;

class RevokeApiTokensOfRemovedUserService
{
    public function __construct(
        private ApiTokenRepository $repository,
    ) {}

    public function __invoke(RevokeApiTokensOfRemovedUserRequest $request, User $user): RevokeApiTokensOfRemovedUserResponse
    {
        $now = new \DateTimeImmutable('NOW');

        $apiTokens = $this->repository->findActiveByUserId($request->userId, $now);

        foreach ($apiTokens as $apiToken) {
            $apiToken->revoke($user->id(), $now);
            $this->repository->add($apiToken);
        }

        return new RevokeApiTokensOfRemovedUserResponse(
            revoked: count($apiTokens),
        );
    }
}
