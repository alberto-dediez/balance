<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Update;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Application\Media\MediaFolderGuard;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateMediaService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaAuthorizer $authorizer,
        private readonly MediaFolderGuard $folderGuard,
    ) {}

    public function __invoke(UpdateMediaRequest $request, User $user): UpdateMediaResponse
    {
        $media = $this->repository->find($request->mediaId);

        if (null === $media) {
            throw new MediaNotFoundException($request->mediaId);
        }

        $this->authorizer->denyUnlessCanDoThat($media, Action::Edit, $user);

        $folderChanged = !self::sameFolder($media->folderId(), $request->folderId);

        if ($folderChanged) {
            $this->folderGuard->assertAssignable($media, $request->folderId, $user);
        }

        $sharingChanged = $request->shared !== $media->isShared();

        if ($sharingChanged) {
            $this->authorizer->denyUnlessCanDoThat($media, Action::Admin, $user);
        }

        if (
            $request->name !== $media->name()
            || $request->description !== $media->description()
            || $request->alternativeText !== $media->alternativeText()
        ) {
            $media->changeDetails($request->name, $request->description, $request->alternativeText);
        }

        if ($request->visibility !== $media->visibility()) {
            $media->changeVisibility($request->visibility);
        }

        if ($sharingChanged) {
            $media->changeSharing($request->shared);
        }

        if ($folderChanged) {
            $media->moveToFolder($request->folderId);
        }

        $this->repository->add($media);

        return new UpdateMediaResponse(
            mediaId: $media->id(),
            name: $media->name(),
            description: $media->description(),
            alternativeText: $media->alternativeText(),
            visibility: $media->visibility(),
            shared: $media->isShared(),
            folderId: $media->folderId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMediaRequest;
    }

    private static function sameFolder(?FolderId $current, ?FolderId $requested): bool
    {
        if (!$current instanceof FolderId || !$requested instanceof FolderId) {
            return !$current instanceof FolderId && !$requested instanceof FolderId;
        }

        return $current->equals($requested);
    }
}
