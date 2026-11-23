<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Remove;

use App\Library\Application\Folder\FolderAuthorizer;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
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
        private readonly FolderAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveFolderRequest $request, User $user): RemoveFolderResponse
    {
        $folder = $this->repository->find($request->folderId);

        if (null === $folder) {
            throw new FolderNotFoundException($request->folderId);
        }

        $this->authorizer->denyUnlessCanDoThat($folder, Action::Remove, $user);

        $releasedMedia = 0;

        foreach ($this->mediaRepository->findByFilter(new MediaFilter(limit: 0, folderIds: [$folder->id()])) as $media) {
            $media->moveToFolder(null);

            $this->mediaRepository->add($media);

            ++$releasedMedia;
        }

        $folder->remove();

        $this->repository->remove($folder);

        return new RemoveFolderResponse(
            folderId: $folder->id(),
            releasedMedia: $releasedMedia,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveFolderRequest;
    }
}
