<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\MarkAsUsed;

use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\User\User;

class MarkApiTokenAsUsedService
{
    public function __construct(private ApiTokenRepository $repository) {}

    public function __invoke(MarkApiTokenAsUsedRequest $request, User $user): MarkApiTokenAsUsedResponse
    {
        $apiToken = $this->repository->find($request->apiTokenId);

        if (null === $apiToken || !$apiToken->belongsTo($user->id())) {
            return new MarkApiTokenAsUsedResponse(marked: false);
        }

        $apiToken->markAsUsed(new \DateTimeImmutable('NOW'));

        $this->repository->add($apiToken);

        return new MarkApiTokenAsUsedResponse(marked: true);
    }
}
