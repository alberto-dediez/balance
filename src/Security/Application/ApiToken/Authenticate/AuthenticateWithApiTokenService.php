<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Authenticate;

use App\Security\Application\ApiToken\ApiTokenPolicy;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use App\Security\Domain\Model\User\User;

class AuthenticateWithApiTokenService
{
    public function __construct(
        private ApiTokenRepository $repository,
        private ApiTokenPolicy $policy,
    ) {}

    public function __invoke(AuthenticateWithApiTokenRequest $request, User $user): AuthenticateWithApiTokenResponse
    {
        $apiToken = $this->repository->findByTokenHash(ApiTokenSecret::hashFor($request->plainValue));

        $now = new \DateTimeImmutable('NOW');

        if (null === $apiToken || !$apiToken->belongsTo($user->id()) || !$this->policy->isUsable($apiToken, $now)) {
            return new AuthenticateWithApiTokenResponse();
        }

        return new AuthenticateWithApiTokenResponse($apiToken->id());
    }
}
