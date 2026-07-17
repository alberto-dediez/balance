<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\EmployeeContribution;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

trait EmployeeContributionPublicControllerTrait
{
    private function checkEmployeeContributionRateLimit(RateLimiterFactory $limiter, Request $request): ?Response
    {
        if (!$limiter->create($request->getClientIp() ?? 'unknown')->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }

    private function employeeContributionInvalidResponse(): Response
    {
        return $this->render('@carbon_footprint/public/employee_contribution/invalid.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
    }

    private function employeeContributionClosedResponse(): Response
    {
        return $this->render('@carbon_footprint/public/employee_contribution/closed.html.twig', [], new Response('', Response::HTTP_CONFLICT));
    }
}
