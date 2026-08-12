<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PublicLanding\GetActive;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;

final class GetActivePublicLandingService
{
    public function __construct(
        private PublicLandingRepository $publicLandingRepository,
        private MediaRepository $mediaRepository,
    ) {}

    public function __invoke(GetActivePublicLandingRequest $request): GetActivePublicLandingResponse
    {
        $publicLanding = $this->publicLandingRepository->findByOwner($request->ownerType, $request->ownerId);

        if (null === $publicLanding || !$publicLanding->isActive() || !$publicLanding->hasContent()) {
            return new GetActivePublicLandingResponse();
        }

        return new GetActivePublicLandingResponse(
            publicLanding: $publicLanding,
            images: $this->publicImages($publicLanding),
        );
    }

    /** @return array<string, Media> */
    private function publicImages(PublicLanding $publicLanding): array
    {
        $mediaIds = [$publicLanding->heroImage()];

        foreach ($publicLanding->blocks() as $block) {
            $mediaIds[] = $block->image();
        }

        $images = [];

        foreach ($mediaIds as $mediaId) {
            if (!$mediaId instanceof MediaId) {
                continue;
            }

            $media = $this->mediaRepository->find($mediaId);

            if ($media instanceof Media && $media->isPublic()) {
                $images[(string) $mediaId] = $media;
            }
        }

        return $images;
    }
}
