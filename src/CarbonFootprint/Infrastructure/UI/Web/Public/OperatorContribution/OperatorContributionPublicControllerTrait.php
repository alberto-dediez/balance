<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OperatorContribution;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\ProviderContributionPublicControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait OperatorContributionPublicControllerTrait
{
    use ProviderContributionPublicControllerTrait;

    private function operatorContributionInvalidResponse(RateLimiterFactory $invalidTokenLimiter, Request $request): Response
    {
        $this->penalizeProviderContributionInvalidToken($invalidTokenLimiter, $request);

        return $this->render('@carbon_footprint/public/operator_contribution/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function operatorContributionClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/operator_contribution/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
