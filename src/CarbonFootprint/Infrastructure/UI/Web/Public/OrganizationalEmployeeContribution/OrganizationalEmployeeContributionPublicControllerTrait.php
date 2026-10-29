<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalEmployeeContribution;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait OrganizationalEmployeeContributionPublicControllerTrait
{
    private function checkOrganizationalEmployeeContributionRateLimit(RateLimiterFactory $limiter, RateLimiterFactory $invalidTokenLimiter, Request $request): ?Response
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

    private function organizationalEmployeeContributionInvalidResponse(RateLimiterFactory $invalidTokenLimiter, Request $request): Response
    {
        $invalidTokenLimiter->create($request->getClientIp() ?? 'unknown')->consume(1);

        return $this->render('@carbon_footprint/public/organizational_employee_contribution/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function organizationalEmployeeContributionClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/organizational_employee_contribution/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
