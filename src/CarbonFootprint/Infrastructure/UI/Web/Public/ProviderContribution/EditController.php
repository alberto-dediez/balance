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

#[Route('/carbon-footprint/contribution/{token}', name: 'public_carbon_footprint_contribution', methods: ['GET'])]
class EditController extends AbstractController
{
    use ProviderContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(string $token, ProviderContributionAuthenticator $authenticator, Request $request): Response
    {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $authentication = $authenticator->authenticate($token);
        } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
            return $this->providerContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        }

        return $this->redirectToRoute(
            $authentication->providerContribution->stepStatus()->activeStepRouteName(),
            ['token' => $token],
        );
    }
}
