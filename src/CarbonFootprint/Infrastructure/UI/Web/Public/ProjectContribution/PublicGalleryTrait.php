<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProjectContribution;

use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;

trait PublicGalleryTrait
{
    /**
     * @param array<MediaId> $gallery
     *
     * @return list<Media>
     */
    private function publicGallery(array $gallery, MediaRepository $mediaRepository): array
    {
        $media = array_map(
            static fn(MediaId $mediaId): ?Media => $mediaRepository->find($mediaId),
            $gallery,
        );

        return array_values(array_filter(
            $media,
            static fn(?Media $media): bool => $media instanceof Media && $media->isPublic(),
        ));
    }
}
