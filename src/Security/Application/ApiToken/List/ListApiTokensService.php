<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\List;

use App\Security\Application\Action;
use App\Security\Application\ApiToken\ApiTokenAuthorizer;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;

class ListApiTokensService
{
    public function __construct(
        private ApiTokenRepository $repository,
        private ApiTokenAuthorizer $authorizer,
        private UserRepository $userRepository,
    ) {}

    public function __invoke(ListApiTokensRequest $request, User $user): ListApiTokensResponse
    {
        if (null === $request->userId) {
            throw new \InvalidArgumentException('User is required');
        }

        $targetUser = $this->userRepository->find($request->userId);

        if (null === $targetUser) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->authorizer->denyUnlessCanDoThat(
            new ApiToken(id: ApiTokenId::generate(), userId: $request->userId, accountId: $targetUser->accountId()),
            Action::List,
            $user,
        );

        $now = new \DateTimeImmutable('NOW');

        $apiTokens = $this->repository->findByFilter($request, $now);

        $totalResults = $this->repository->totalResultsByFilter($request, $now);

        return new ListApiTokensResponse($apiTokens, $totalResults);
    }
}
