<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/contribution/{token}/answered', name: 'public_carbon_footprint_contribution_answered', methods: ['GET'])]
class AnsweredController extends AbstractController
{
    use ProviderContributionPublicControllerTrait;

    public function __construct(private RateLimiterFactory $providerContributionTokenLimiter) {}

    public function __invoke(string $token, ProviderContributionAuthenticator $authenticator, Request $request): Response
    {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $authenticator->authenticate($token);
        } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
            return $this->providerContributionInvalidResponse();
        }

        return $this->render('@carbon_footprint/public/contribution/answered.html.twig');
    }
}
