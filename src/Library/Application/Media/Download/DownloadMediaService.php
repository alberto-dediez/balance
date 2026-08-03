<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Download;

use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class DownloadMediaService
{
    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaStorage $storage,
        private readonly MediaAuthorizer $authorizer,
    ) {}

    public function __invoke(DownloadMediaRequest $request, User $user): DownloadMediaResponse
    {
        $media = $this->repository->find($request->mediaId);

        if (null === $media) {
            throw new MediaNotFoundException($request->mediaId);
        }

        $this->authorizer->denyUnlessCanDoThat($media, Action::View, $user);

        if (!$this->storage->exists($media->path())) {
            throw new NotStoredMediaException(sprintf('Media "%s" contents are not available', (string) $media->id()));
        }

        return new DownloadMediaResponse(
            stream: $this->storage->read($media->path()),
            fileName: $media->fileName(),
            mimeType: $media->mimeType(),
            size: $media->size(),
        );
    }
}
