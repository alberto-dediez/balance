<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/provider-contribution/{token}/answered', name: 'public_organizational_provider_contribution_answered', methods: ['GET'])]
class AnsweredController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OrganizationalProviderContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(string $token, OrganizationalProviderContributionAuthenticator $authenticator, Request $request): Response
    {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $authentication = $authenticator->authenticate($token);
        } catch (OrganizationalProviderContributionTokenInvalidException|OrganizationalProviderContributionIgnoredException) {
            return $this->organizationalProviderContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        }

        $this->brandFor($authentication->contribution->accountId());

        return $this->render('@carbon_footprint/public/provider_contribution/answered.html.twig', [
            'token' => $token,
        ]);
    }
}
