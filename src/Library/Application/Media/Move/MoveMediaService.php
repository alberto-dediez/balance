<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Move;

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

class MoveMediaService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaAuthorizer $authorizer,
        private readonly MediaFolderGuard $folderGuard,
    ) {}

    public function __invoke(MoveMediaRequest $request, User $user): MoveMediaResponse
    {
        $media = $this->repository->find($request->mediaId);

        if (null === $media) {
            throw new MediaNotFoundException($request->mediaId);
        }

        $this->authorizer->denyUnlessCanDoThat($media, Action::Edit, $user);

        $this->folderGuard->assertAssignable($media, $request->folderId, $user);

        if (!self::sameFolder($media->folderId(), $request->folderId)) {
            $media->moveToFolder($request->folderId);

            $this->repository->add($media);
        }

        return new MoveMediaResponse(
            mediaId: $media->id(),
            folderId: $media->folderId(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof MoveMediaRequest;
    }

    private static function sameFolder(?FolderId $current, ?FolderId $requested): bool
    {
        if (!$current instanceof FolderId || !$requested instanceof FolderId) {
            return !$current instanceof FolderId && !$requested instanceof FolderId;
        }

        return $current->equals($requested);
    }
}
