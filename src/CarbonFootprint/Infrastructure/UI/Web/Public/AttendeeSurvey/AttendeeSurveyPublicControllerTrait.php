<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait AttendeeSurveyPublicControllerTrait
{
    private function checkAttendeeSurveyRateLimit(RateLimiterFactory $limiter, Request $request): ?Response
    {
        if (!$limiter->create($request->getClientIp() ?? 'unknown')->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }

    private function attendeeSurveyInvalidResponse(): Response
    {
        return $this->render('@carbon_footprint/public/attendee_survey/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function attendeeSurveyClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/attendee_survey/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
