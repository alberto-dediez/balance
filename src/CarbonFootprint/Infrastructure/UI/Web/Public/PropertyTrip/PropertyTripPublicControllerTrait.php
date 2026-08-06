<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait PropertyTripPublicControllerTrait
{
    private function propertyTripInvalidResponse(PublicFormRateLimiter $rateLimiter, Request $request): Response
    {
        $rateLimiter->penalizeInvalidToken($request);

        return $this->render('@carbon_footprint/public/property_trip/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }
}
