<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait ProviderContributionPublicControllerTrait
{
    private function checkProviderContributionRateLimit(RateLimiterFactory $limiter, RateLimiterFactory $invalidTokenLimiter, Request $request): ?Response
    {
        $clientIp = $request->getClientIp() ?? 'unknown';

        if (!$limiter->create($clientIp)->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        if ($invalidTokenLimiter->create($clientIp)->consume(0)->getRemainingTokens() <= 0) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }

    private function penalizeProviderContributionInvalidToken(RateLimiterFactory $invalidTokenLimiter, Request $request): void
    {
        $invalidTokenLimiter->create($request->getClientIp() ?? 'unknown')->consume(1);
    }

    private function providerContributionInvalidResponse(RateLimiterFactory $invalidTokenLimiter, Request $request): Response
    {
        $this->penalizeProviderContributionInvalidToken($invalidTokenLimiter, $request);

        return $this->render('@carbon_footprint/public/contribution/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function providerContributionClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/contribution/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
