<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OperatorContribution;

use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionTokenInvalidException;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/operator-contribution/{token}/answered', name: 'public_operator_contribution_answered', methods: ['GET'])]
class AnsweredController extends AbstractController
{
    use BrandedPublicControllerTrait;
    use OperatorContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        OperatorContributionAuthenticator $authenticator,
        OperatorContributionEmissionsCalculator $emissionsCalculator,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $authentication = $authenticator->authenticate($token);
            $companyCarbonFootprint = $authenticator->locateCompanyCarbonFootprint($authentication);
        } catch (OperatorContributionTokenInvalidException|OperatorContributionIgnoredException) {
            return $this->operatorContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        }

        if (!$authentication->contribution->status()->isAnswered()) {
            return $this->redirectToRoute('public_operator_contribution', ['token' => $token]);
        }

        $this->brandFor($authentication->contribution->accountId());

        return $this->render('@carbon_footprint/public/operator_contribution/answered.html.twig', [
            'token' => $token,
            'summary' => $emissionsCalculator->summary($authentication->contribution),
            'editable' => !$companyCarbonFootprint->isCompleted(),
        ]);
    }
}
