<?php

declare(strict_types=1);

namespace App\Library\Application\Media\List;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
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
        private readonly CurrentAccountFolders $folders,
    ) {}

    public function __invoke(ListMediaRequest $request, User $user): ListMediaResponse
    {
        $accountId = $request->accountId;

        $media = new Media(id: MediaId::generate(), accountId: $accountId);

        $this->authorizer->denyUnlessCanDoThat($media, Action::List, $user);

        $request->nameFolderIds = $request->searchInFolderNames && !in_array($request->name, [null, ''], true)
            ? array_map(static fn(Folder $folder): FolderId => $folder->id(), $this->folders->matchingName($request->name))
            : [];

        return new ListMediaResponse(
            $this->repository->findByFilter($request),
            $this->repository->totalResultsByFilter($request),
        );
    }
}
