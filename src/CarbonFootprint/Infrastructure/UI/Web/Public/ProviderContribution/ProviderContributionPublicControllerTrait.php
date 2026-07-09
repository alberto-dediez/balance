<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait ProviderContributionPublicControllerTrait
{
    private function checkProviderContributionRateLimit(RateLimiterFactory $limiter, Request $request): ?Response
    {
        if (!$limiter->create($request->getClientIp() ?? 'unknown')->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }

    private function providerContributionInvalidResponse(): Response
    {
        return $this->render('@carbon_footprint/public/contribution/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function providerContributionClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/contribution/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
