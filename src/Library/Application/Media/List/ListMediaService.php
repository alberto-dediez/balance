<?php

declare(strict_types=1);

namespace App\Library\Application\Media\List;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListMediaService
{
    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaAuthorizer $authorizer,
    ) {}

    public function __invoke(ListMediaRequest $request, User $user): ListMediaResponse
    {
        $accountId = $request->accountId;

        $media = new Media(id: MediaId::generate(), accountId: $accountId);

        $this->authorizer->denyUnlessCanDoThat($media, Action::List, $user);

        return new ListMediaResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
