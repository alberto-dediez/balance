<?php

declare(strict_types=1);

namespace App\Library\Application\Media;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Library\Domain\Model\Media\Media;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;

class MediaFolderGuard
{
    public function __construct(
        private readonly FolderRepository $repository,
        private readonly FolderAuthorizer $authorizer,
    ) {}

    public function assertAssignable(Media $media, ?FolderId $folderId, User $user): void
    {
        if (!$folderId instanceof FolderId) {
            return;
        }

        $folder = $this->repository->find($folderId);

        if (!$folder instanceof Folder || !$this->sameAccount($folder->accountId(), $media->accountId())) {
            throw new FolderNotFoundException($folderId);
        }

        $this->authorizer->denyUnlessCanDoThat($folder, Action::View, $user);
    }

    private function sameAccount(?AccountId $folderAccountId, ?AccountId $mediaAccountId): bool
    {
        if (!$folderAccountId instanceof AccountId || !$mediaAccountId instanceof AccountId) {
            return !$folderAccountId instanceof AccountId && !$mediaAccountId instanceof AccountId;
        }

        return $folderAccountId->equals($mediaAccountId);
    }
}
