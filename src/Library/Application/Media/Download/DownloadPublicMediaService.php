<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Download;

use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;

class DownloadPublicMediaService
{
    public function __construct(
        private readonly MediaRepository $repository,
        private readonly MediaStorage $storage,
    ) {}

    public function __invoke(DownloadMediaRequest $request): DownloadMediaResponse
    {
        $media = $this->repository->find($request->mediaId);

        if (null === $media || !$media->isPublic()) {
            throw new MediaNotFoundException($request->mediaId);
        }

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
