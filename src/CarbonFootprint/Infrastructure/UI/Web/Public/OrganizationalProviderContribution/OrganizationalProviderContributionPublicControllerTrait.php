<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalProviderContribution;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\ProviderContributionPublicControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait OrganizationalProviderContributionPublicControllerTrait
{
    use ProviderContributionPublicControllerTrait;

    private function organizationalProviderContributionInvalidResponse(RateLimiterFactory $invalidTokenLimiter, Request $request): Response
    {
        $this->penalizeProviderContributionInvalidToken($invalidTokenLimiter, $request);

        return $this->render('@carbon_footprint/public/provider_contribution/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function organizationalProviderContributionClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/provider_contribution/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
