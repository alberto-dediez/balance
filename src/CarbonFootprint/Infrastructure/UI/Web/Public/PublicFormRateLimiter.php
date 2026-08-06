<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Rate limiters guarding a public form reached through a token: one instance per flow,
 * so a flood on one of them never locks the others out.
 */
final class PublicFormRateLimiter
{
    public function __construct(
        private readonly RateLimiterFactory $tokenLimiter,
        private readonly RateLimiterFactory $invalidTokenLimiter,
        private readonly RateLimiterFactory $placesLimiter,
    ) {}

    /** @return Response|null The response to return right away when the client is over the limit. */
    public function check(Request $request): ?Response
    {
        $clientIp = $this->clientIp($request);

        if (!$this->tokenLimiter->create($clientIp)->consume(1)->isAccepted()) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        if ($this->invalidTokenLimiter->create($clientIp)->consume(0)->getRemainingTokens() <= 0) {
            return new Response('Demasiados intentos. Por favor espere.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }

    public function penalizeInvalidToken(Request $request): void
    {
        $this->invalidTokenLimiter->create($this->clientIp($request))->consume(1);
    }

    public function checkPlaces(Request $request): bool
    {
        return $this->placesLimiter->create($this->clientIp($request))->consume(1)->isAccepted();
    }

    private function clientIp(Request $request): string
    {
        return $request->getClientIp() ?? 'unknown';
    }
}
