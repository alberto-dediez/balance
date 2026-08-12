<?php

declare(strict_types=1);

namespace App\Library\Application\Media\List;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class ListMediaService
{
    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaAuthorizer $authorizer,
    ) {}

    public function __invoke(ListMediaRequest $request, User $user): ListMediaResponse
    {
        $media = new Media(MediaId::generate());

        $this->authorizer->denyUnlessCanDoThat($media, Action::List, $user);

        $filter = $this->scopeToUserAccount($request, $user);

        return new ListMediaResponse(
            $this->repository->findByFilter($filter),
            $this->repository->totalResultsByFilter($filter),
        );
    }

    private function scopeToUserAccount(ListMediaRequest $request, User $user): ListMediaRequest
    {
        $filter = clone $request;

        $filter->accountId = $user->accountId();
        $filter->withoutAccount = !$user->accountId() instanceof AccountId;

        return $filter;
    }
}
