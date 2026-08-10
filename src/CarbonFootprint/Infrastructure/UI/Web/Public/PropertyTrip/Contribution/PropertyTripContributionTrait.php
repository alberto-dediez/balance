<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenResponse;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Domain\Model\Account\AccountId;

trait PropertyTripContributionTrait
{
    private function resolveLink(
        string $token,
        GetTripCalculationLinkByTokenService $getByToken,
    ): ?GetTripCalculationLinkByTokenResponse {
        try {
            return $getByToken(new GetTripCalculationLinkByTokenRequest($token));
        } catch (TripCalculationLinkTokenInvalidException) {
            return null;
        }
    }

    private function resolveTripCalculation(
        string $tripCalculationId,
        GetTripCalculationLinkByTokenResponse $link,
        TripCalculationRepository $tripCalculationRepository,
    ): ?TripCalculation {
        $tripCalculation = $tripCalculationRepository->find(new TripCalculationId($tripCalculationId));
        $linkId = $tripCalculation?->tripCalculationLinkId();

        if (!$linkId instanceof TripCalculationLinkId || !$linkId->equals($link->tripCalculationLink->id())) {
            return null;
        }

        return $tripCalculation;
    }

    private function accountIdOf(GetTripCalculationLinkByTokenResponse $link): ?AccountId
    {
        return $link->tripCalculationLink->accountId() ?? $link->property->accountId();
    }

    /**
     * The guest is not logged in, so only the media anyone can reach may be painted: anything else
     * would end up as a broken image.
     *
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
