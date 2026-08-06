<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait AttendeeSurveyPublicControllerTrait
{
    private function attendeeSurveyInvalidResponse(PublicFormRateLimiter $rateLimiter, Request $request): Response
    {
        $rateLimiter->penalizeInvalidToken($request);

        return $this->render('@carbon_footprint/public/attendee_survey/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function attendeeSurveyClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/attendee_survey/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
