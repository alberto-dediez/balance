<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicLanding;

use App\CarbonFootprint\Application\PublicLanding\GetActive\GetActivePublicLandingRequest;
use App\CarbonFootprint\Application\PublicLanding\GetActive\GetActivePublicLandingService;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use Symfony\Component\HttpFoundation\Response;

trait PublicLandingRenderTrait
{
    /**
     * Paints the informative page configured for the owner, or nothing at all when there is
     * none active: the caller then falls back to sending the visitor straight to the form.
     *
     * @param array<string, string> $ctaRouteParams
     */
    private function renderPublicLanding(
        GetActivePublicLandingService $getActivePublicLanding,
        PublicLandingOwnerType $ownerType,
        string $ownerId,
        string $ownerName,
        string $ctaRoute,
        array $ctaRouteParams,
    ): ?Response {
        $response = $getActivePublicLanding(new GetActivePublicLandingRequest($ownerType, $ownerId));

        if (null === $response->publicLanding) {
            return null;
        }

        return $this->render('@carbon_footprint/public/landing.html.twig', [
            'publicLanding' => $response->publicLanding,
            'images' => $response->images,
            'ownerName' => $ownerName,
            'ctaPath' => $this->generateUrl($ctaRoute, $ctaRouteParams),
        ]);
    }
}
