<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Remove;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Application\Media\Remove\RemoveMediaRequest;
use App\Library\Application\Media\Remove\RemoveMediaService;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderFilter;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderNotRemovedException;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveFolderService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly FolderRepository $repository,
        private readonly MediaRepository $mediaRepository,
        private readonly RemoveMediaService $removeMedia,
        private readonly FolderAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveFolderRequest $request, User $user): RemoveFolderResponse
    {
        $folder = $this->repository->find($request->folderId);

        if (null === $folder) {
            throw new FolderNotFoundException($request->folderId);
        }

        $this->authorizer->denyUnlessCanDoThat($folder, Action::Remove, $user);

        $removedMedia = 0;
        $failedMedia = 0;
        $removedSubfolders = 0;

        foreach ($this->repository->findByFilter(new FolderFilter(limit: 0, parentId: $folder->id())) as $child) {
            [$removed, $failed] = $this->removeMediaOf($child, $user);

            $removedMedia += $removed;
            $failedMedia += $failed;

            if (0 === $failed) {
                $child->remove();

                $this->repository->remove($child);

                ++$removedSubfolders;
            }
        }

        [$removed, $failed] = $this->removeMediaOf($folder, $user);

        $removedMedia += $removed;
        $failedMedia += $failed;

        if ($failedMedia > 0) {
            throw new FolderNotRemovedException($folder->id(), $failedMedia);
        }

        $folder->remove();

        $this->repository->remove($folder);

        return new RemoveFolderResponse(
            folderId: $folder->id(),
            removedMedia: $removedMedia,
            removedSubfolders: $removedSubfolders,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveFolderRequest;
    }

    /**
     * @return array{int, int}
     */
    private function removeMediaOf(Folder $folder, User $user): array
    {
        $removed = 0;
        $failed = 0;

        foreach ($this->mediaRepository->findByFilter(new MediaFilter(limit: 0, folderIds: [$folder->id()])) as $media) {
            try {
                ($this->removeMedia)(new RemoveMediaRequest($media->id()), $user);

                ++$removed;
            } catch (\Exception) {
                ++$failed;
            }
        }

        return [$removed, $failed];
    }
}
