<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\Step;

use App\CarbonFootprint\Application\ProviderContribution\AnswerGeneralInfo\AnswerProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\ProviderContribution\AnswerGeneralInfo\AnswerProviderContributionGeneralInfoService;
use App\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo\GetProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo\GetProviderContributionGeneralInfoService;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution\ProviderContributionPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/contribution/{token}/general', name: 'public_carbon_footprint_contribution_general')]
class GeneralController extends AbstractController
{
    use ProviderContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetProviderContributionGeneralInfoService $getGeneralInfo,
        AnswerProviderContributionGeneralInfoService $answerGeneralInfo,
        Request $request,
    ): Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contribution_general_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $general = $request->request->all('general');

            try {
                $answerGeneralInfo(new AnswerProviderContributionGeneralInfoRequest(
                    token: $token,
                    companyName: !empty($general['companyName']) ? (string) $general['companyName'] : null,
                    companyType: !empty($general['companyType']) ? (string) $general['companyType'] : null,
                    service: !empty($general['service']) ? (string) $general['service'] : null,
                    workersAssembly: !empty($general['workersAssembly']) ? (int) $general['workersAssembly'] : null,
                    workersCelebration: !empty($general['workersCelebration']) ? (int) $general['workersCelebration'] : null,
                    workersBreakdown: !empty($general['workersBreakdown']) ? (int) $general['workersBreakdown'] : null,
                ));
            } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
                return $this->providerContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
            } catch (ProviderContributionClosedException) {
                return $this->providerContributionClosedResponse();
            }

            return $this->redirectToRoute('public_carbon_footprint_contribution_assembly', ['token' => $token]);
        }

        try {
            $response = $getGeneralInfo(new GetProviderContributionGeneralInfoRequest($token));
        } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
            return $this->providerContributionInvalidResponse($this->providerContributionInvalidTokenLimiter, $request);
        }

        return $this->render('@carbon_footprint/public/contribution/step/general.html.twig', [
            'token' => $token,
            'providerContribution' => $response->providerContribution,
            'event' => $response->event,
        ]);
    }
}
