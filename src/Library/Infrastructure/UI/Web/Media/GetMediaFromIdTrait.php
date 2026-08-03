<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;

trait GetMediaFromIdTrait
{
    private function getMediaFromId(string $mediaId, MediaRepository $mediaRepository): Media
    {
        $media = $mediaRepository->find(new MediaId($mediaId));

        if (null === $media) {
            throw $this->createNotFoundException('Media not found');
        }

        return $media;
    }
}
